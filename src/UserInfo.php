<?php
/**
 * User info class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkUser;

use \PDO as PDO;

class UserInfo
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
     * get user info
     *
     * @param $userID
     * @return array
     */
    public function getUserInfo($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM users
            WHERE id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);

        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $result = [
                'id' => $row['id'],
                'uuid' => $row['uuid'],
                'user_id' => $row['id'],
                'user_email' => $row['email'],
                'user_provider_type' => $this->userProviderType($row['provider_type']),
                'user_acc' => '',
                'user_avatar' => $row['avatar'],
                'user_nickname' => $row['name'],
                'user_locale' => $row['locale'],
                'user_account_type' => $this->userAccountType($this->getUserRoles($userID) ?? ''),
                'user_roles' => $this->getUserRoles($userID) ?? [],
                'user_role_quota' => $this->getUserPermissionsQuota($userID) ?? [],
                'user_permissions' => $this->getUserPermissions($userID) ?? [],
                'user_services' => $this->getUserServices($userID) ?? '',
                'user_limits' => [
                    'site' => 12,
                    'section' => 5,
                ],
                'user_crm' => $this->getExtraCrmData($userID) ?? [],
                'permissions' => $this->getUserBuilderPermission($this->getUserPermissions($userID) ?? []),
            ];
            $result['user_acc'] = $result['user_email'] . '#' . $result['user_provider_type'];

            return $result;
        }

        return false;
    }

    /**
     * get roles
     *
     * @param $userID
     * @return array
     */
    public function getServicesFromUser($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_services
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get user service
     *
     * @param $userID
     * @return array
     */
    public function getUserServices($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_services
            WHERE user_id = :user_id AND deactivate = 0
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);
        $services = [];
        if ($query->rowCount() > 0) {
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $val) {
                foreach ($this->getServices($val["service_id"]) as $val2) {
                    $str = json_decode($val2["text"], true);
                    $text = $str["zh_TW"]["title"];
                    $services[] = [
                        "service_id" => $val["service_id"],
                        "text" => $text,
                    ];
                }
            }
        }
        return $services;
    }

    /**
     * get user roles
     *
     * @param $userID
     * @return array
     */
    public function getUserRoles($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_roles
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);
        $roles = [];
        if ($query->rowCount() > 0) {
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $val) {
                foreach ($this->getRole($val["role_id"]) as $val2) {
                    array_push($roles, $val2["unique_name"]);
                }
            }
            return array_unique($roles);
        }
        return $roles;
    }

    /**
     * get user roles
     *
     * @param $userID
     * @return array
     */
    public function getRoleFromUser($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_roles
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get permissions from role
     *
     * @param $userID
     * @return array
     */
    public function getPermissionsFromRole($role_id = null)
    {
        $sql = <<<EOF
            SELECT * FROM role_permissions
            WHERE role_id = :role_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':role_id' => $role_id,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get roles
     *
     * @param $userID
     * @return array
     */
    public function getRole($role_id = null)
    {
        $sql = <<<EOF
            SELECT * FROM roles
            WHERE id = :role_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':role_id' => $role_id,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get user permissions
     *
     * @param $userID
     * @return array
     */
    public function getUserPermissions($userID = null)
    {
        $permissions_array = [];
        if (count($this->getServicesFromUser($userID)) > 0) {
            foreach ($this->getServicesFromUser($userID) as $val) {
                if (count($this->getServicePermissions($val["service_id"])) > 0) {
                    foreach ($this->getServicePermissions($val["service_id"]) as $val2) {
                        array_push($permissions_array, $val2["permissions_id"]);
                    }
                }
            }
        }

        if (count($this->getRoleFromUser($userID)) > 0) {
            foreach ($this->getRoleFromUser($userID) as $val) {
                if (count($this->getPermissionsFromRole($val["role_id"])) > 0) {
                    foreach ($this->getPermissionsFromRole($val["role_id"]) as $val2) {
                        array_push($permissions_array, $val2["permissions_id"]);
                    }
                }
            }
        }

        // freeshop user role
        if (count($this->getPermissionsFromUser($userID)) > 0) {
            $result = $this->getPermissionsFromUser($userID);
            foreach ($result as $val) {
                array_push($permissions_array, $val["permissions_id"]);
            }
        }

        $user_permissions = [];
        $_permissions_array = array_unique($permissions_array);
        foreach ($_permissions_array as $val) {
            if (count($this->getPermissions($val)) > 0) {
                foreach ($this->getPermissions($val) as $val2) {
                    array_push($user_permissions, $val2["unique_name"]);
                }
            }
        }

        return $user_permissions;
    }

    /**
     * get service permissions
     *
     * @param $service_id
     * @return array
     */
    public function getServicePermissions($service_id = null)
    {
        $sql = <<<EOF
            SELECT * FROM service_permissions
            WHERE service_id = :service_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':service_id' => $service_id,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get permissions
     *
     * @param $permission_id
     * @return array
     */
    public function getPermissions($permission_id = null)
    {
        $sql = <<<EOF
            SELECT * FROM permissions
            WHERE id = :permission_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':permission_id' => $permission_id,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get services
     *
     * @param $service_id
     * @return array
     */
    public function getServices($service_id = null)
    {
        $sql = <<<EOF
            SELECT * FROM services
            WHERE id = :service_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':service_id' => $service_id,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get user account type
     *
     * @param $type
     * @return int
     */
    public function userAccountType($type)
    {
        switch ($type) {
            case 'admin':
                return 1;
            case 'vip':
            case 'premium':
            case 'testing':
                return 2;
            default:
                return 3;
        }
    }

    /**
     * get user provider type
     *
     * @param $type
     * @return int
     */
    public function userProviderType($type)
    {
        switch ($type) {
            case 0:
                return 'default';
            case 1:
                return 'facebook';
            case 2:
                return 'google';
            case 3:
                return 'line';
            default:
                return '';
        }
    }

    /**
     * get user services
     *
     * @param $data
     * @return array
     */
    public function userServices($data)
    {
        $returnData = [];
        if (empty($data)) {
            return [];
        } else {
            foreach ($data as $type => $services) {
                switch ($type) {
                    case 'plan':
                    case 'additional':
                        foreach ($services as $service) {
                            switch ($service) {
                                case '4cbbb6c9b8c1993f25adc601867b7917':
                                    array_push($returnData, [
                                        'service_id' => 1,
                                        'text' => '白金月繳',
                                    ]);
                                    break;
                                case '5994d46e05899d30fae4d0834d0cefc9':
                                    array_push($returnData, [
                                        'service_id' => 2,
                                        'text' => '白金年繳',
                                    ]);
                                    break;
                                case '793749b3f3dca5aff3a2cc740dd48663':
                                    array_push($returnData, [
                                        'service_id' => 3,
                                        'text' => '白金三年繳',
                                    ]);
                                    break;
                                case '88efa90d501f45f59309e8132e55c89f':
                                    array_push($returnData, [
                                        'service_id' => 43,
                                        'text' => '達人組合年繳 - 品牌網站加強版',
                                    ]);
                                    break;
                                case '5be00891e015c85aefb72fc209944036':
                                    array_push($returnData, [
                                        'service_id' => 44,
                                        'text' => '達人組合年繳  - 購物網站加強版',
                                    ]);
                                    break;
                                case '5a35a388e29b22ef88d5502b77583498':
                                    array_push($returnData, [
                                        'service_id' => 45,
                                        'text' => '達人組合年繳  - 品牌+購物網站加強版',
                                    ]);
                                    break;
                                case 'c04452dda757d474e1071c54926a9a33':
                                    array_push($returnData, [
                                        'service_id' => 51,
                                        'text' => '達人組合年繳  - Google 搜尋全方位網站加強版',
                                    ]);
                                    break;
                                case 'b6edb58881fe6e46b9c7af64692a5f86':
                                    array_push($returnData, [
                                        'service_id' => 47,
                                        'text' => '達人組合三年繳 - 品牌網站加強版',
                                    ]);
                                    break;
                                case 'd6bc9d0a1fab5bf0dd87484616a4bc31':
                                    array_push($returnData, [
                                        'service_id' => 48,
                                        'text' => '達人組合三年繳  - 購物網站加強版',
                                    ]);
                                    break;
                                case '05fda11050ed8f7db3ddfa234624f33c':
                                    array_push($returnData, [
                                        'service_id' => 49,
                                        'text' => '達人組合三年繳  - 品牌+購物網站加強版',
                                    ]);
                                    break;
                                case 'ce7e449d9faa3fc4d6403aa5815dd9a3':
                                    array_push($returnData, [
                                        'service_id' => 52,
                                        'text' => '達人組合三年繳  - Google 搜尋全方位網站加強版',
                                    ]);
                                    break;
                                case '8c07f3098f7de905d73c47150aa9efcd':
                                    array_push($returnData, [
                                        'service_id' => 4,
                                        'text' => '追蹤碼安裝',
                                    ]);
                                    break;
                                case 'fa57f15403eef809b750f44fcf53606e':
                                    array_push($returnData, [
                                        'service_id' => 5,
                                        'text' => 'Google 分析報表寄送',
                                    ]);
                                    break;
                                case 'ef52fa11e0702edfa82efde1656c8a4a':
                                    array_push($returnData, [
                                        'service_id' => 6,
                                        'text' => '1 對 1 電話諮詢',
                                    ]);
                                    break;
                                case 'a5d0506b19a3a611cd296589caf155aa':
                                    array_push($returnData, [
                                        'service_id' => 7,
                                        'text' => '網站圖示',
                                    ]);
                                    break;
                                case '77c9dc7152cf95d5754c69c5dd114e77':
                                    array_push($returnData, [
                                        'service_id' => 8,
                                        'text' => '微電商網站代號變更',
                                    ]);
                                    break;
                                case 'afeae76b04f552528ff761d441e202b0':
                                    array_push($returnData, [
                                        'service_id' => 9,
                                        'text' => '自動轉址設定',
                                    ]);
                                    break;
                                case '1b8e735c38f4398b83a03cc12c1dcf2f':
                                    array_push($returnData, [
                                        'service_id' => 10,
                                        'text' => '子網域設定',
                                    ]);
                                    break;
                                case 'dccc0ae11a314ad38c3682a5d3868f1f':
                                    array_push($returnData, [
                                        'service_id' => 11,
                                        'text' => 'Google 搜尋引擎',
                                    ]);
                                    break;
                                case '55cb5045c14558090b5a6591553456e8':
                                    array_push($returnData, [
                                        'service_id' => 12,
                                        'text' => '客服連結按鈕',
                                    ]);
                                    break;
                                case '92fc40abc228dc1f1158ef7ea26b66e6':
                                    array_push($returnData, [
                                        'service_id' => 13,
                                        'text' => '威旭客戶專屬白金',
                                    ]);
                                    break;
                                case '3c41b2349628e3451e8a2bbc13418c4f':
                                    array_push($returnData, [
                                        'service_id' => 14,
                                        'text' => '白金日繳',
                                    ]);
                                    break;
                                case '190ff2ccc4304aab384b59cac5f100e9':
                                    array_push($returnData, [
                                        'service_id' => 15,
                                        'text' => '多語言選單',
                                    ]);
                                    break;
                                case 'c4db3b906be96ef11c01a14e229cd484':
                                    array_push($returnData, [
                                        'service_id' => 26,
                                        'text' => 'Yahoo 關鍵字品牌推廣 - 小資限定',
                                    ]);
                                    break;
                                case 'fef93fa8deb3ee76b717f7ea20cf5acb':
                                    array_push($returnData, [
                                        'service_id' => 27,
                                        'text' => 'Yahoo 關鍵字品牌推廣 - 達人限定',
                                    ]);
                                    break;
                                case '9477bb92b8a79849f0d62ee4b360f9b9':
                                    array_push($returnData, [
                                        'service_id' => 33,
                                        'text' => 'Facebook 商業擴充功能 (FBE) + 轉換 API',
                                    ]);
                                    break;
                                case 'e9ace661b48be40f8f4da680c700ed97':
                                    array_push($returnData, [
                                        'service_id' => 50,
                                        'text' => 'Google 品牌關鍵字推廣',
                                    ]);
                                    break;
                                case '30e35aa8bf07903ad7628852a1c14db6':
                                    array_push($returnData, [
                                        'service_id' => 53,
                                        'text' => '客製化網站圖片',
                                    ]);
                                    break;
                                case '6ad16dcba3b246c8b12553d6dab6e28c':
                                    array_push($returnData, [
                                        'service_id' => 54,
                                        'text' => '15 秒圖文剪輯影音',
                                    ]);
                                    break;
                                case '8886ae1551ddc98ca0a44ccfb8989603':
                                    array_push($returnData, [
                                        'service_id' => 55,
                                        'text' => 'Facebook 社群小編',
                                    ]);
                                    break;
                            }
                        }
                        break;
                }
            }
            return $returnData;
        }
    }

    /**
     * get user builder permission
     *
     * @param $permissions
     * @return array
     */
    public function getUserBuilderPermission($permissions = [])
    {
        $returnData = new \stdClass();
        $returnData->admin = false; // 管理者權限 true (管理者) | false (非管理者)
        $returnData->sitelimit = 12; // 網站數量限制 int | 0 (無上限)
        $returnData->pagelimit = 0; // 頁數限制 (無上限)
        $returnData->sectionlimit = 5; // 新編輯器的 section 數量限制 int | 0 (無上限)
        $returnData->footer = 1; // holkee footer 顯示與否 1 (顯示) | 0 (隱藏)
        $returnData->sitecode = 0; // 自行編輯網站代號權限 1 (開放) | 0 (關閉)
        $returnData->gareport = 0; // google 報表查看權限 1 (開放) | 0 (關閉)
        $returnData->trackingcode = 0; // 網站追蹤服務 1 (啟用) | 0 (停用)
        $returnData->shopsite = 0; // 微電商使用權限 1 (啟用) | 0 (停用)
        $returnData->productlimit = 0; // 微電商商品上架數量限制 (一律無上限)
        $returnData->moduleusegroup = 'all'; // 已無實質作用, 為確保正常運行給予初始參數
        $returnData->premium = 0; // 是否具有白金會員權限 1 (白金) | 0 (非白金)

        if (in_array('admin', $permissions)) {
            $returnData->admin = true;
            $returnData->sitelimit = 0;
            $returnData->sectionlimit = 0;
            $returnData->footer = 0;
            $returnData->sitecode = 1;
            $returnData->gareport = 1;
            $returnData->trackingcode = 1;
            $returnData->shopsite = 1;
            $returnData->premium = 1;
        }
        if (in_array('unlimited_sites', $permissions)) {
            $returnData->sitelimit = 0;
        }
        if (in_array('unlimited_sections', $permissions)) {
            $returnData->sectionlimit = 0;
        }
        if (in_array('remove_official_logo', $permissions)) {
            $returnData->footer = 0;
        }
        if (in_array('custom_url', $permissions)) {
            $returnData->sitecode = 1;
        }
        if (in_array('pageviews_report', $permissions)) {
            $returnData->gareport = 1;
        }
        if (in_array('tracking_code', $permissions)) {
            $returnData->trackingcode = 1;
        }
        if (in_array('shop_site', $permissions)) {
            $returnData->shopsite = 1;
        }
        if (in_array('premium_sections', $permissions)) {
            $returnData->premium = 1;
        }
        return $returnData;
    }

    /**
     * get extra data crm
     *
     * @param $userID
     * @return array
     */
    public function getExtraCrmData($userID = null)
    {
        $sql = <<<EOF
            SELECT report_status FROM user_extra_crm
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get permissions from user
     *
     * @param $userID
     * @return array
     */
    public function getPermissionsFromUser($user_id = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_permissions
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $user_id,
        ]);
        $result = [];
        if ($query->rowCount() > 0) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /**
     * get user role quota
     *
     * @param $userID
     * @return array
     */
    public function getUserRoleQuota($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_roles
            WHERE user_id = :user_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
        ]);
        $quota = [];
        $quotas = [];
        if ($query->rowCount() > 0) {
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $val) {
                foreach ($this->getRole($val["role_id"]) as $val2) {
                    $quota['brand_quota'] = $val2['brand_quota'];
                    $quota['ec_quota'] = $val2['ec_quota'];
                    $quota['ezec_quota'] = $val2['ezec_quota'];
                    array_push($quotas, $quota);
                }
            }
            return array_unique($quotas);
        }
        return $quotas;
    }

    /**
     * get user permissions quota
     *
     * @param $userID
     * @return array
     */
    public function getUserPermissionsQuota($userID = null)
    {
        $sql = <<<EOF
            SELECT * FROM user_permissions
            WHERE user_id = :user_id AND permissions_id = :permissions_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':user_id' => $userID,
            ':permissions_id' => $this->getPermissionsId("site_quota")
        ]);
        $quota = [];
        $quotas = [];
        if ($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            $quota['original_brand_quota'] = $result['original_brand_quota'];
            $quota['original_ec_quota'] = $result['original_ec_quota'];
            $quota['original_ezec_quota'] = $result['original_ezec_quota'];

            $quota['available_brand_quota'] = $result['available_brand_quota'];
            $quota['available_ec_quota'] = $result['available_ec_quota'];
            $quota['available_ezec_quota'] = $result['available_ezec_quota'];

            $quota['used_brand_quota'] = $result['used_brand_quota'];
            $quota['used_ec_quota'] = $result['used_ec_quota'];
            $quota['used_ezec_quota'] = $result['used_ezec_quota'];
            array_push($quotas, $quota);
            
            return $quotas;
        }
        return $quotas;
    }

    /**
     * Get permissions id
     * 
     * @return int
     */
    public function getPermissionsId($unique_name)
    {
        $sql = 'SELECT id FROM permissions ';
        $sql.= 'WHERE unique_name = :unique_name';
        $query = $this->database->prepare($sql);
        $query->execute([
            ':unique_name' => $unique_name,
        ]);
        $permissions = $query->fetch(PDO::FETCH_ASSOC);
        if (!empty($permissions['id'])) {
            return $permissions['id'];
        }
        return false;
    }

}
