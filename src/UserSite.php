<?php
/**
 * User site class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkUser;

use \PDO as PDO;
use \Exception as Exception;

class UserSite
{
    /**
     * database
     *
     * @var object
     */
    private $database;

    /**
     * initialize
     */
    public function __construct($db = null)
    {
        $this->database = $db;
    }

    /**
     * get site info
     *
     * @param $site_id
     * @return array
     */
    public function getSite($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM sites WHERE id = :site_id AND deleted_at IS NULL
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':site_id' => $site_id
        ]);
        
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * del custom domain
     *
     * @param $site_id
     * @return bool
     */
    public function delCustomDomain($site_id)
    {
        $sql = <<<EOF
        UPDATE sites SET custom_domain = NULL, is_subdomain = 0 WHERE id = :site_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':site_id' => $site_id
        ]);
        
        if ($query->rowCount() > 0) {
            return true;
        }
        return false;
    }
}