<?php
class ItemInfo {
	private $dbh = null;
	private $p1 = null;
	private $p2 = null;
	private $p3 = null;
	function __construct($dbh)
	{
		$this->dbh = $dbh;
		# 錯誤的話, 就不做了
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$this->p1 = $this->dbh->prepare("select * from item_info where legoID=:legoID
			and item_type not in ('Boxes','Instructions')");
		$this->p2 = $this->dbh->prepare("update item_info set url=:url where id=:id");
		$this->p3 = $this->dbh->prepare("insert into `item_info_changelog` (`id`,`action`,`desc`,`created_at`)
                                values (:id,:action,:desc,now())");
	}

	function __destruct()
	{
		unset($dbh);
		unset($p1);
		unset($p2);
		unset($p3);
	}

	function getLegoID($lego_id)
	{
		try {
			$this->p1->bindParam(':legoID',$lego_id,PDO::PARAM_STR);
			$this->p1->execute();
			if($this->p1->rowCount() == 0)
                                return null;
			return $this->p1->fetchAll(PDO::FETCH_OBJ);
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return null;
		}
		#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Finish'."\n",3,"./log/ItemInfo.txt");
	}
	function updateItemURL($librick_id,$url)
	{
		try {
			$this->p2->bindParam(':id',$librick_id,PDO::PARAM_STR);
			$this->p2->bindParam(':url',$url,PDO::PARAM_STR);
			$this->p2->execute();
			if($this->p2->rowCount() == 1)
			{
				$LogArray = array('id'=>$librick_id,'action'=>'update');
				$LogArray['desc'] = sprintf("url='%s'",$url);
				$this->insertLog($LogArray);
				unset($LogArray);
                                return true;
			}
			return false;
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return true;
		}
	}

	function insertLog($update_data)
	{
		try {
			$this->p3->execute($update_data);
			if($this->p3->rowCount() === 1)
				return true;
			return false;
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return false;
		}
	}
}
?>
