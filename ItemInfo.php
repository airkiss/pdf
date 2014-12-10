<?php
class ItemInfo {
	private $dbh = null;
	private $p1 = null;
	private $p2 = null;
	function __construct($dbh)
	{
		$this->dbh = $dbh;
		# 錯誤的話, 就不做了
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$this->p1 = $this->dbh->prepare("select * from item_info where bricklink=:bricklink 
			and item_type not in ('Boxes','Instructions')");
		$this->p2 = $this->dbh->prepare("update item_info set url=:url where id=:id");
	}

	function __destruct()
	{
		
	}

	function getLibrickID($bricklink_id)
	{
		try {
			$this->p1->bindParam(':bricklink',$bricklink_id,PDO::PARAM_STR);
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
			if($this->p2->rowCount() == 0)
                                return false;
			return true;
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return true;
		}
	}
}
?>
