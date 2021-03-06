<?php
/**
 * Nc2ToNc3MenuBehavior
 *
 * @copyright Copyright 2017, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('Nc2ToNc3BaseBehavior', 'Nc2ToNc3.Model/Behavior');

/**
 * Nc2ToNc3MenuBehavior
 *
 */
class Nc2ToNc3MenuBehavior extends Nc2ToNc3BaseBehavior {

/**
 * Get Log argument.
 *
 * @param Model $model Model using this behavior.
 * @param array $nc2MenuDetail Nc2MenuDetail data.
 * @return string Log argument
 */
	public function getLogArgument(Model $model, $nc2MenuDetail) {
		return $this->__getLogArgument($nc2MenuDetail);
	}

/**
 * Generate generateNc3MenuFrameSettingData data.
 *
 * Data sample
 * data[MenuFrameSetting][id]:
 * data[MenuFrameSetting][frame_key]:
 * data[MenuFrameSetting][display_type]:main
 *
 * @param Model $model Model using this behavior.
 * @param array $nc2MenuDetail Nc2MenuDetail data.
 * @return array Nc3MenuFrameSetting data.
 */
	public function generateNc3MenuFrameSettingData(Model $model, $nc2MenuDetail) {
		/* @var $Nc2ToNc3Frame Nc2ToNc3Frame */
		$Nc2ToNc3Frame = ClassRegistry::init('Nc2ToNc3.Nc2ToNc3Frame');
		$nc2BlockId = $nc2MenuDetail['Nc2MenuDetail']['block_id'];

		$frameMap = $Nc2ToNc3Frame->getMap($nc2BlockId);
		if (!$frameMap) {
			$message = __d('nc2_to_nc3', '%s does not migration.', $this->__getLogArgument($nc2MenuDetail));
			$this->_writeMigrationLog($message);
			return [];
		}

		/* @var $Nc2ToNc3Map Nc2ToNc3Map */
		$Nc2ToNc3Map = ClassRegistry::init('Nc2ToNc3.Nc2ToNc3Map');
		$mapIdList = $Nc2ToNc3Map->getMapIdList('MenuFrameSetting', $nc2BlockId);
		if ($mapIdList) {
			return [];	// 移行済み
		}

		$nc3DisplayType = $this->__convertDisplayType($nc2MenuDetail);
		if (!$nc3DisplayType) {
			return [];
		}
		/* @var $Nc2ToNc3User Nc2ToNc3User */
		$Nc2ToNc3User = ClassRegistry::init('Nc2ToNc3.Nc2ToNc3User');
		$data = [
			'frame_key' => $frameMap['Frame']['key'],
			'display_type' => $nc3DisplayType,
			'created_user' => $Nc2ToNc3User->getCreatedUser($nc2MenuDetail['Nc2MenuDetail']),
			'created' => $this->_convertDate($nc2MenuDetail['Nc2MenuDetail']['insert_time']),
		];

		return $data;
	}

/**
 * Generate Nc3MenuFramePage or Nc3MenuFrameRoom data.
 *
 * Data sample
 * data[MenuRooms][1][MenuFramesRoom][room_id]:1
 * data[MenuRooms][1][MenuFramesRoom][frame_key]:
 * data[MenuRooms][1][MenuFramesRoom][is_hidden]:0
 * data[Menus][1][4][MenuFramesPage][id]:1
 * data[Menus][1][4][MenuFramesPage][frame_key]:
 * data[Menus][1][4][MenuFramesPage][page_id]:
 * data[Menus][1][4][MenuFramesPage][is_hidden]:1
 * data[MenuFrameSetting][is_private_room_hidden]
 *
 * @param Model $model Model using this behavior.
 * @param array $nc2MenuDetail Nc2MenuDetail data.
 * @param array $nc3MenuFrameSetting Nc3MenuFrameSetting data.
 * @return array Nc3MenuFrameSetting data for room.
 */
	public function generateNc3MenuFramePageOrRoomData(Model $model, $nc2MenuDetail, $nc3MenuFrameSetting) {
		$nc2PageId = $nc2MenuDetail['Nc2MenuDetail']['page_id'];
		if ($nc2PageId == '-1') {
			// プライベートルームの処理
			$nc3MenuFrameSetting['MenuFrameSetting']['is_private_room_hidden'] = '1';
			return $nc3MenuFrameSetting;
		}

		/* @var $Nc2ToNc3Page Nc2ToNc3Page */
		$Nc2ToNc3Page = ClassRegistry::init('Nc2ToNc3.Nc2ToNc3Page');
		$pageMap = $Nc2ToNc3Page->getMap($nc2PageId);
		if (!$pageMap) {
			$message = __d('nc2_to_nc3', '%s does not migration.', $this->__getLogArgument($nc2MenuDetail));
			$this->_writeMigrationLog($message);

			return $nc3MenuFrameSetting;
		}

		/* @var $Nc2ToNc3User Nc2ToNc3User */
		$Nc2ToNc3User = ClassRegistry::init('Nc2ToNc3.Nc2ToNc3User');

		$nc2RoomId = $nc2MenuDetail['Nc2MenuDetail']['room_id'];
		$nc3RoomId = $pageMap['Box']['room_id'];
		if ($nc2PageId == $nc2RoomId) {
			$nc3MenuFrameSetting['MenuRooms'][$nc3RoomId]['MenuFramesRoom'] = [
				'frame_key' => $nc3MenuFrameSetting['MenuFrameSetting']['frame_key'],
				'room_id' => $nc3RoomId,
				'is_hidden' => '1',
				'created_user' => $Nc2ToNc3User->getCreatedUser($nc2MenuDetail['Nc2MenuDetail']),
				'created' => $this->_convertDate($nc2MenuDetail['Nc2MenuDetail']['insert_time']),
			];

			return $nc3MenuFrameSetting;
		}

		$nc3PageId = $pageMap['Page']['id'];
		$nc3MenuFrameSetting['Menus'][$nc3RoomId][$nc3PageId]['MenuFramesPage'] = [
			'frame_key' => $nc3MenuFrameSetting['MenuFrameSetting']['frame_key'],
			'page_id' => $nc3PageId,
			'is_hidden' => '1',
			'created_user' => $Nc2ToNc3User->getCreatedUser($nc2MenuDetail['Nc2MenuDetail']),
			'created' => $this->_convertDate($nc2MenuDetail['Nc2MenuDetail']['insert_time']),
		];

		return $nc3MenuFrameSetting;
	}

/**
 * Add other　room and page data.
 *
 * @param Model $model Model using this behavior.
 * @param array $nc3MenuFrameSetting Nc3MenuFrameSetting data.
 * @return array Nc3MenuFrameSetting data.
 */
	public function addOtherRoomAndPageData(Model $model, $nc3MenuFrameSetting) {
		// 同じデータなので、propertyで保持しようと思ったが、unsetするタイミングとか変な感じなので、とりあえず毎回取得する
		// propertyに保持するタイミングと、unsetのタイミングを合わせようと初期処理っぽくやろうとしたが、
		// Nc3MenuFrameSetting.created_user,createdがまだなかったので、とりあえず毎回取得する

		if ($nc3MenuFrameSetting['MenuFrameSetting']['display_type'] !== 'header') {
			return $nc3MenuFrameSetting;
		}

		// Nc3MenuFrameSetting.display_type が'header' の場合、Nc2MenuDetail に存在しないRoom,Pageのデータを作成する
		// Room::getReadableRoomsConditionsでプライベートルームの条件ははいらないはず。
		// @see https://github.com/NetCommons3/Menus/blob/3.1.0/Controller/MenusAppController.php#L52-L56
		// @see https://github.com/NetCommons3/Menus/blob/3.1.0/Controller/MenusController.php#L58-L63
		// @see https://github.com/NetCommons3/Rooms/blob/3.1.0/Model/Behavior/RoomBehavior.php#L93-L95
		/* @var $Room Room */
		$Room = ClassRegistry::init('Rooms.Room');
		$query = $Room->getReadableRoomsConditions();
		$query['fields'] = 'Room.id';
		$nc3RoomIds = $Room->find('list', $query);

		/* @var $Nc2ToNc3Map Nc2ToNc3Map */
		$Nc2ToNc3Map = ClassRegistry::init('Nc2ToNc3.Nc2ToNc3Map');
		$query = [
			'fields' => 'nc3_id',
			'conditions' => [
				'model_name' => 'Room',
				'nc3_id' => $nc3RoomIds,
			],
			'recursive' => -1
		];
		$nc3RoomIds = $Nc2ToNc3Map->find('list', $query);

		// @see https://github.com/NetCommons3/Menus/blob/3.1.0/Controller/MenusAppController.php#L52-L56
		// @see https://github.com/NetCommons3/Menus/blob/3.1.0/Controller/MenusController.php#L66-L70
		/* @var $Page Page */
		$Page = ClassRegistry::init('Pages.Page');
		$query = [
			'fields' => [
				'id',
				'room_id',
			],
			'conditions' => [
				'room_id' => $nc3RoomIds
			],
			'recursive' => -1
		];
		$nc3PageList = $Page->find('list', $query);

		$query = [
			'fields' => 'nc3_id',
			'conditions' => [
				'model_name' => 'Page',
				'nc3_id' => array_keys($nc3PageList),
			],
			'recursive' => -1
		];
		$mapedNc3PageIds = $Nc2ToNc3Map->find('list', $query);

		foreach ($nc3PageList as $nc3PageId => $nc3RoomId) {
			if (!isset($nc3MenuFrameSetting['MenuRooms'][$nc3RoomId])) {
				$nc3MenuFrameSetting['MenuRooms'][$nc3RoomId]['MenuFramesRoom'] = [
					'frame_key' => $nc3MenuFrameSetting['MenuFrameSetting']['frame_key'],
					'room_id' => $nc3RoomId,
					'is_hidden' => '0',
					'created_user' => $nc3MenuFrameSetting['MenuFrameSetting']['created_user'],
					'created' => $nc3MenuFrameSetting['MenuFrameSetting']['created'],
				];
			}

			if (!in_array($nc3PageId, $mapedNc3PageIds)) {
				continue;
			}

			if (!isset($nc3MenuFrameSetting['Menus'][$nc3RoomId][$nc3PageId])) {
				$nc3MenuFrameSetting['Menus'][$nc3RoomId][$nc3PageId]['MenuFramesPage'] = [
					'frame_key' => $nc3MenuFrameSetting['MenuFrameSetting']['frame_key'],
					'page_id' => $nc3PageId,
					'is_hidden' => '0',
					'created_user' => $nc3MenuFrameSetting['MenuFrameSetting']['created_user'],
					'created' => $nc3MenuFrameSetting['MenuFrameSetting']['created'],
				];
			}

		}

		return $nc3MenuFrameSetting;
	}

/**
 * Get Log argument.
 *
 * @param array $nc2MenuDetail Nc2MenuDetail data.
 * @return string Log argument
 */
	private function __getLogArgument($nc2MenuDetail) {
		return 'nc2MenuDetail ' .
			'block_id:' . $nc2MenuDetail['Nc2MenuDetail']['block_id'] .
			'page_id:' . $nc2MenuDetail['Nc2MenuDetail']['page_id'];
	}

/**
 * Convert to Nc3MenuFrameSetting display_type.
 *
 * @param array $nc2MenuDetail Nc2MenuDetail data.
 * @return Model converted date.
 */
	private function __convertDisplayType($nc2MenuDetail) {
		$nc3DisplayType = null;

		// Nc2Block::belongsTo['Nc2Page'] みたいな定義でいけそうだが個別に取得
		// @see https://github.com/cakephp/cakephp/blob/2.9.6/lib/Cake/Model/Model.php#L893
		// @see https://github.com/cakephp/cakephp/blob/2.9.6/lib/Cake/Model/Model.php#L1084

		/* @var $Nc2Block AppModel */
		/* @var $Nc2Page AppModel */
		$Nc2Block = $this->_getNc2Model('blocks');
		$Nc2Page = $this->_getNc2Model('pages');
		$nc2Block = $Nc2Block->findByBlockId(
			$nc2MenuDetail['Nc2MenuDetail']['block_id'],
			[
				'page_id',
				'temp_name'
			],
			null,
			-1
		);
		if (!$nc2Block) {
			$message = __d('nc2_to_nc3', '%s does not migration.', $this->__getLogArgument($nc2MenuDetail));
			$this->_writeMigrationLog($message);

			return $nc3DisplayType;
		}
		$nc2Page = $Nc2Page->findByPageId($nc2Block['Nc2Block']['page_id'], 'display_position', null, -1);
		$nc2DisplayPosition = $nc2Page['Nc2Page']['display_position'];

		$displayTypeMap = [
			'1' => 'major',
			'2' => 'minor',
			'3' => 'header'
		];
		if (isset($displayTypeMap[$nc2DisplayPosition])) {
			return $displayTypeMap[$nc2DisplayPosition];
		}

		$nc2TemplateName = $nc2Block['Nc2Block']['temp_name'];
		if (strpos($nc2TemplateName, 'header') === 0 ||
			strpos($nc2TemplateName, 'jq_gnavi') === 0) {
			return $displayTypeMap['3'];
		}

		return 'main';
	}

}
