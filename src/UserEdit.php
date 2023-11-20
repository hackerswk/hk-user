<?php
/**
 * User edit class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkUser;

use \PDO as PDO;
use \Exception as Exception;

class UserEdit
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
     * insert into user role.
     * 
     * @param $user_id, $role_id
     * @return bool
     */
    public function setRole($user_id, $role_id)
    {
        try {
            $this->delRole($user_id);
            $sql = 'INSERT INTO user_roles
                    SET user_id = :user_id, role_id = :role_id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id,
                ':role_id' => $role_id
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    } 

    /**
     * delete user role.
     * 
     * @param $user_id
     * @return bool
     */
    public function delRole($user_id)
    {
        try {
            $sql = 'DELETE FROM user_roles
                    WHERE user_id = :user_id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    } 

    /**
     * get role quota
     *
     * @param $role_id
     * @return array
     */
    public function getRoleQuota($role_id)
    {
        $sql = <<<EOF
            SELECT * FROM roles
            WHERE id = :role_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':role_id' => $role_id
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get user roles
     *
     * @param $user_id
     * @return array
     */
    public function getUserRoles($user_id)
    {
        $sql = <<<EOF
            SELECT * FROM user_roles
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $user_id
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * insert into user permission quota.
     * 
     * @param $user_id, $role_id, $quota
     * @return bool
     */
    public function setQuota($user_id, $permissions_id, $quota = null)
    {
        try {
            $userRole = $this->getUserRoles($user_id);
            $roleQuota = $this->getRoleQuota($userRole['role_id']);
            $this->delQuota($user_id);
            $sql = 'INSERT INTO user_permissions
                    SET 
                    user_id = :user_id, 
                    permissions_id = :permissions_id,
                    original_brand_quota = :original_brand_quota,
                    original_ec_quota = :original_ec_quota,
                    original_ezec_quota = :original_ezec_quota,
                    available_brand_quota = :available_brand_quota,
                    available_ec_quota = :available_ec_quota,
                    available_ezec_quota = :available_ezec_quota,
                    used_brand_quota = :used_brand_quota,
                    used_ec_quota = :used_ec_quota,
                    used_ezec_quota = :used_ezec_quota';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id,
                ':permissions_id' => $permissions_id,
                ':original_brand_quota' => isset($quota['original_brand_quota']) ? $quota['original_brand_quota'] : 0,
                ':original_ec_quota' => isset($quota['original_ec_quota']) ? $quota['original_ec_quota'] : 0,
                ':original_ezec_quota' => isset($quota['original_ezec_quota']) ? $quota['original_ezec_quota'] : 0,
                ':available_brand_quota' => isset($quota['available_brand_quota']) ? $quota['available_brand_quota'] : $roleQuota['brand_quota'],
                ':available_ec_quota' => isset($quota['available_ec_quota']) ? $quota['available_ec_quota'] : $roleQuota['ec_quota'],
                ':available_ezec_quota' => isset($quota['available_ezec_quota']) ? $quota['available_ezec_quota'] : $roleQuota['ezec_quota'],
                ':used_brand_quota' => isset($quota['used_brand_quota']) ? $quota['used_brand_quota'] : 0,
                ':used_ec_quota' => isset($quota['used_ec_quota']) ? $quota['used_ec_quota'] : 0,
                ':used_ezec_quota' => isset($quota['used_ezec_quota']) ? $quota['used_ezec_quota'] : 0
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    
    /**
     * delete user permissions.
     * 
     * @param $user_id
     * @return bool
     */
    public function delQuota($user_id)
    {
        try {
            $sql = 'DELETE FROM user_permissions
                    WHERE user_id = :user_id AND permissions_id = :permissions_id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id,
                ':permissions_id' => 28
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    
    /**
     * insert into user services.
     * 
     * @param $user_id, $service_id
     * @return bool
     */
    public function setServices($user_id, $service_id, $activated_at = null, $expire_at = null)
    {
        try {
            $sql = 'INSERT INTO user_services
                    SET user_id = :user_id, service_id = :service_id, activated_at = :activated_at, expire_at = :expire_at';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id,
                ':service_id' => $service_id,
                ':activated_at' => $activated_at,
                ':expire_at' => $expire_at
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    } 

    /**
     * update user services.
     * 
     * @param $id, $expire_at
     * @return bool
     */
    public function updateServices($id, $expire_at)
    {
        try {
            $sql = 'UPDATE user_services
                    SET expire_at = :expire_at WHERE id = :id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':id' => $id,
                ':expire_at' => $expire_at
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    
    /**
     * set user for freeshop.
     * 
     * @param $user_id
     * @return bool
     */
    public function setUserForFreeshop($user_id, $quota)
    {
        try {
            $activated_at = date("Y-m-d H:i:s", time());
            $expire_at = date("Y-m-d H:i:s", strtotime($activated_at) + 30 * 86400);
            $this->database->beginTransaction();
            if (!$this->setServices($user_id, 63, $activated_at, $expire_at)) {
                $this->database->rollBack();
                return false;
            }
            if (!$this->setQuota($user_id, 28, $quota)) {
                $this->database->rollBack();
                return false;
            }
            $this->database->commit();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }
    
    /**
     * delete user sites from user id.
     * 
     * @param $user_id
     * @return bool
     */
    public function delUserSiteFromUserId($user_id)
    {
        try {
            $sql = 'DELETE FROM user_sites
                    WHERE user_id = :user_id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * delete user sites from site id.
     * 
     * @param $site_id
     * @return bool
     */
    public function delUserSiteFromSiteId($site_id)
    {
        try {
            $sql = 'DELETE FROM user_sites
                    WHERE site_id = :site_id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':site_id' => $site_id
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * set services expired.
     * 
     * @param $user_id, $service_id, $has_exceed
     * @return bool
     */
    public function setServiceExpired($user_id, $service_id, $has_exceed = 1)
    {
        try {
            $sql = 'UPDATE user_services
                    SET has_exceed = :has_exceed WHERE user_id = :user_id AND service_id = :service_id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':user_id' => $user_id,
                ':service_id' => $service_id,
                ':has_exceed' => $has_exceed
            ]);
            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
}
