<?php
class PDFInfo {
	private $dbh = null;
	private $p1 = null;
	private $p2 = null;
	function __construct($dbh)
	{
		$this->dbh = $dbh;
		# 錯誤的話, 就不做了
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$this->p1 = $this->dbh->prepare("select * from pdf_info where found=0 and url is not null");
		$this->p2 = $this->dbh->prepare("update pdf_info set found=1 where no=:no");
	}

	function __destruct()
	{
		
	}

	function getAllPDF()
	{
		try {
			$this->p1->execute();
			if($this->p1->rowCount() == 0)
                                return null;
			return $this->p1->fetchAll(PDO::FETCH_OBJ);
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/PDFInfo.txt");
			return null;
		}
		#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Finish'."\n",3,"./log/PDFInfo.txt");
	}

	function updateFound($no)
	{
		try {
			$this->p2->bindParam(':no',$no,PDO::PARAM_STR);
			$this->p2->execute();
			if($this->p2->rowCount() == 0)
                                return false;
			return true;
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/PDFInfo.txt");
			return true;
		}
	}
}
?>
