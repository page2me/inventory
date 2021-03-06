<?php
/**
 * @filesource modules/index/views/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Repair\Setup;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=member.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var mixed
     */
    private $statuses;
    /**
     * @var mixed
     */
    private $operators;

    /**
     * ตารางรายชื่อสมาชิก
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        $isAdmin = Login::checkPermission($login, 'can_manage_repair');
        // สถานะการซ่อม
        $this->statuses = \Repair\Status\Model::create();
        $status = $request->request('status', -1)->toInt();
        // รายชื่อช่างซ่อม
        $operator_id = $request->request('operator_id')->toInt();
        $this->operators = \Repair\Operator\Model::create();
        $operators = array();
        if ($isAdmin) {
            $operators[0] = '{LNG_all items}';
            $operator = $operator_id;
        } else {
            $operator_id = $login['id'];
            $operator = array(0, $operator_id);
        }
        foreach ($this->operators->toSelect() as $k => $v) {
            if ($isAdmin || $k == $operator_id) {
                $operators[$k] = $v;
            }
        }
        // URL สำหรับส่งให้ตาราง
        $uri = self::$request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Repair\Setup\Model::toDataTable($operator, $status),
            'perPage' => $request->cookie('repair_perPage', 30)->toInt(),
            'sort' => $request->cookie('repair_sort', 'create_date desc')->toString(),
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'phone', 'job_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/repair/model/setup/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}',
                    ),
                ),
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'name' => 'operator_id',
                    'text' => '{LNG_Operator}',
                    'options' => $operators,
                    'value' => $operator_id,
                ),
                array(
                    'name' => 'status',
                    'text' => '{LNG_Repair status}',
                    'options' => array(-1 => '{LNG_all items}') + $this->statuses->toSelect(),
                    'value' => $status,
                ),
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_Informer}',
                    'sort' => 'name',
                ),
                'phone' => array(
                    'text' => '{LNG_Phone}',
                    'class' => 'center',
                ),
                'equipment' => array(
                    'text' => '{LNG_Equipment}',
                ),
                'create_date' => array(
                    'text' => '{LNG_Received date}',
                    'class' => 'center',
                    'sort' => 'create_date',
                ),
                'operator_id' => array(
                    'text' => '{LNG_Operator}',
                    'class' => 'center',
                ),
                'status' => array(
                    'text' => '{LNG_Repair status}',
                    'class' => 'center',
                    'sort' => 'status',
                ),
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'phone' => array(
                    'class' => 'center',
                ),
                'create_date' => array(
                    'class' => 'center',
                ),
                'operator_id' => array(
                    'class' => 'center',
                ),
                'status' => array(
                    'class' => 'center',
                ),
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'status' => array(
                    'class' => 'icon-list button orange notext',
                    'id' => ':id',
                    'title' => '{LNG_Repair status}',
                ),
                array(
                    'class' => 'icon-report button purple notext',
                    'href' => $uri->createBackUri(array('module' => 'repair-detail', 'id' => ':id')),
                    'title' => '{LNG_Repair job description}',
                ),
            ),
        ));
        // สามารถแก้ไขใบรับซ่อมได้
        if ($isAdmin) {
            $table->buttons[] = array(
                'class' => 'icon-edit button green notext',
                'href' => $uri->createBackUri(array('module' => 'repair-receive', 'id' => ':id')),
                'title' => '{LNG_Edit} {LNG_Repair details}',
            );
        }
        // save cookie
        setcookie('repair_perPage', $table->perPage, time() + 2592000, '/', null, HOST, true);
        setcookie('repair_sort', $table->sort, time() + 2592000, '/', null, HOST, true);

        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        $item['phone'] = self::showPhone($item['phone']);
        $item['status'] = '<mark class=term style="background-color:'.$this->statuses->getColor($item['status']).'">'.$this->statuses->get($item['status']).'</mark>';
        $item['operator_id'] = $this->operators->get($item['operator_id']);

        return $item;
    }
}
