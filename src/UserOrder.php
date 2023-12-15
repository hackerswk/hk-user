<?php
/**
 * User order class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkUser;

use \PDO as PDO;
use \Exception as Exception;

class UserOrder
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
     * get renewal orders
     *
     * @return array
     */
    public function getRenewalOrders()
    {
        $sql = <<<EOF
            SELECT * FROM (SELECT o.id,
            o.user_id,
            (oi.frequency * 86400) frequency,
            oi.service_unique_name,
            UNIX_TIMESTAMP(o.created_at) created_at
            FROM user_orders o
            INNER JOIN user_order_items oi ON oi.order_id = o.id
            WHERE o.platform = :platform
            AND o.card_key IS NOT NULL AND o.card_key <> ""
            AND o.card_token IS NOT NULL AND o.card_token <> ""
            AND o.status = 1
            AND o.deleted_at IS NULL
            AND oi.frequency > 0) t
            GROUP BY frequency, id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':platform' => 'TAPPAY'
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get last transaction record
     *
     * @param $orderId
     * @return array
     */
    public function getLastTransactionRecord($order_id)
    {
        $sql = <<<EOF
            SELECT id, order_id,
            IF(deleted_at IS NULL, 1, 0) status,
            UNIX_TIMESTAMP(created_at) created_at
            FROM user_order_periods
            WHERE order_id = :order_id
            ORDER BY id DESC LIMIT 0, 1
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':order_id' => $order_id
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get unpaid orders
     *
     * @return array
     */
    public function getUnpaidOrders()
    {
        $now = time();
        $from = $now - 3600;
        $sql = <<<EOF
            SELECT o.id
            FROM user_orders o
            WHERE o.status = 0
            AND o.pay_status = 0 AND o.deleted_at IS NULL
            AND UNIX_TIMESTAMP(o.created_at) <= :from
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':from' => $from
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get order callbacks
     *
     * @param $order_id
     * @return array
     */
    public function getOrderCallbacks($order_id)
    {
        $sql = <<<EOF
            SELECT * 
            FROM user_order_callbacks
            WHERE order_id = :order_id
            ORDER BY id DESC
            LIMIT 0, 1
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':order_id' => $order_id
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get order from uuid
     *
     * @param $order_id, $uuid
     * @return array
     */
    public function getOrderFromUUID($user_id, $uuid)
    {
        $sql = <<<EOF
            SELECT o.id, o.user_id, o.created_at, o.status
            FROM user_orders o
            INNER JOIN user_order_items oi ON oi.order_id = o.id
            WHERE oi.service_unique_name = :uuid
            AND o.user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $user_id,
            ':uuid' => $uuid
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * set user order status
     *
     * @param $order_id, $status
     * @return bool
     */
    public function setUserOrderStatus($order_id, $status)
    {
        $sql = <<<EOF
            UPDATE user_orders
            SET status = :status
            WHERE id = :order_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':order_id' => $order_id,
            ':status' => $status
        ]);
        //$result = [];
        if ($query->rowCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * set user order feedback.
     * 
     * @param $order_id, $user_id, $ec, $brand_site, $stop_using_reason, $why_stop, $suggestion
     * @return bool
     */
    public function setUserOrderFeedback($order_id, $user_id, $ec, $brand_site, $stop_using_reason, $why_stop = null, $suggestion = null)
    {
        try {
            $sql = 'INSERT INTO user_order_feedback
                    SET
                    order_id = :order_id, 
                    user_id = :user_id, 
                    ec = :ec,
                    brand_site = :brand_site,
                    stop_using_reason = :stop_using_reason,
                    why_stop = :why_stop,
                    suggestion = :suggestion';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':order_id' => $order_id,
                ':user_id' => $user_id,
                ':ec' => $ec,
                ':brand_site' => $brand_site,
                ':stop_using_reason' => $stop_using_reason,
                ':why_stop' => $why_stop,
                ':suggestion' => $suggestion
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
     * get last paid record
     *
     * @param $order_id
     * @return array
     */
    public function getLastPaidRecord($order_id)
    {
        $sql = <<<EOF
            SELECT order_id, created_at AS last_paid_at FROM user_order_periods
            WHERE order_id = :order_id 
            AND refunded = 0
            AND deleted_at IS NULL
            ORDER BY id DESC LIMIT 0, 1
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':order_id' => $order_id
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }
}