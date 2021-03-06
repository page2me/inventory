<?php
/**
 * @filesource modules/repair/views/history.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Repair\History;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=repair-history.
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
     * ประวัติการแจ้งซ่อม
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // สถานะการซ่อม
        $this->statuses = \Repair\Status\Model::create();
        $status = $request->request('status', -1)->toInt();
        $this->operators = \Repair\Operator\Model::create();
        // URL สำหรับส่งให้ตาราง
        $uri = self::$request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Repair\History\Model::toDataTable($login['id'], $status),
            'perPage' => $request->cookie('history_perPage', 30)->toInt(),
            'sort' => $request->cookie('history_sort', 'create_date desc')->toString(),
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('equipment'),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'name' => 'status',
                    'text' => '{LNG_Repair status}',
                    'options' => array(-1 => '{LNG_all items}') + $this->statuses->toSelect(),
                    'value' => $status,
                ),
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
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
                array(
                    'class' => 'icon-report button purple notext',
                    'href' => $uri->createBackUri(array('module' => 'repair-detail', 'id' => ':id')),
                    'title' => '{LNG_Repair job description}',
                ),
            ),
        ));
        // save cookie
        setcookie('history_perPage', $table->perPage, time() + 2592000, '/', null, HOST, true);
        setcookie('history_sort', $table->sort, time() + 2592000, '/', null, HOST, true);

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
        $item['status'] = '<mark class=term style="background-color:'.$this->statuses->getColor($item['status']).'">'.$this->statuses->get($item['status']).'</mark>';
        $item['operator_id'] = $this->operators->get($item['operator_id']);

        return $item;
    }
}
