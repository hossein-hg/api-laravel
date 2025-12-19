<?php

namespace app\components;

use app\models\Groups;
use app\models\Products;
use app\models\VipAccountHistory;
use Yii;
use yii\base\component;
use yii\base\Exception;
use app\models\Person;

class Partner extends component
{
    public function IsPartner($group_id)
    {
        if (Yii::$app->user->identity) {
            $user_id = Yii::$app->user->identity->id;
            $group = Groups::find()->where(['id' => $group_id])->one();
            if ($group) {
                $main_group = Groups::find()->where(['id' => $group->parent])->one();
                // die(var_dump($main_group->parent));

                if ($main_group) {
                    $person = Person::find()->where(['user_id' => $user_id, 'group_id' => $main_group->parent])->one();
                    if ($person) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    $person = Person::find()->where(['user_id' => $user_id, 'group_id' => $group->id])->one();
                    if ($person) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }
    public function IsVip($person_id)
    {
        // $user_id = Yii::$app->user->identity->id;

        $person = Person::find()->where(['id' => $person_id])->one();

        if ($person) {
            $vip = VipAccountHistory::find()->where(['person_id' => $person->id, 'status' => 1])->one();
            if ($vip) {
                if (VipAccountHistory::dateDifference($vip->date_expire) > 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function AllowOffer($product)
    {
        $user = Yii::$app->user->id;
        $product = Products::findOne($product);
        $person = Person::find()->where(['user_id' => $user])->one();
        if ($person) {

            $person_id = $person->id;
        } else {
            if ($user == 6) {
                $person_id = null;
            }
        }
        if ($person_id == $product->store_id) {
            return true;
        } else {
            return false;
        }
        // if ($product != NULL && $product->store_id == NULL && $user == 6) {
        //     return TRUE;
        // }

        // if (!$product) {
        //     return FALSE;
        // }

        // $store = Person::find()->where(['user_id' => $user])->one();
        // if (!$store) {
        //     return TRUE;
        // }

        // if ($product->store_id != $store->id) {
        //     return FALSE;
        // }
        // return $this->IsVip($store->id);
    }
    public function CheckPartner($id)
    {
        $user = Yii::$app->user->id;
        $person = Person::find()->where(['id' => $id, 'user_id' => $user ,'status'=>1])->one();
        if ($person) {
            return true;
        } else {
            return  false;
        }
    }
}
