#!/usr/bin/php -q
<?php
require_once('autoload.php');
ini_set('date.timezone','UTC');
ini_set("memory_limit","512M");
ini_set("max_execution_time",300000);
ini_set("max_input_time",600000);
$URL_PATTERN = "http://service.lego.com/Views/Service/Pages/BIService.ashx/GetCompletionList?prefixText=";
$URL_TAILER = "&count=1000";
$PDF_PATTERN = "http://service.lego.com/Views/Service/Pages/BIService.ashx/GetCompletionListHtml?prefixText=";
$PDF_TAILER = "&fromIdx=0";

function ScanAllTable($DB,$url_pattern,$url_tailer,$created_at)
{
	$web = new WebService();
	try {
		$dbh = new PDO($DB['DSN_DEV'],$DB['DB_USER'], $DB['DB_PWD'],
			array( PDO::ATTR_PERSISTENT => false));
		# 錯誤的話, 就不做了
		$dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$p = $dbh->prepare("insert into `pdf_info` set `no`=?, `desc`=?,
			 `updated_at`=now(), `created_at`=now() on duplicate key update
			`updated_at`=now()");
		for($i = 0; $i <= 9; $i++)
		{
			$url = $url_pattern . $i . $url_tailer;
			$all_records = json_decode( $web->GetWebService($url) );
			echo __METHOD__ . ' ['.date('Y-m-d H:i:s') . '] '. $i . ' -> ' . count($all_records) . "\n";
			foreach ($all_records as $record)
			{
				$data = explode(" ",$record);
				$no = array_shift($data);
				$desc = implode(" ",$data);
				$p->execute(array($no,$desc));
			}
		}
		unset($web);
	} catch (PDOException $e) {
		unset($web);
		$errMsg = "Error: " . $e->getMessage() . "\n";
		die($errMsg);
	}
}

function FetchPDFByTime($DB,$pdf_pattern,$pdf_tailer,$created_at,$debug=false)
{
	$web = new WebService();
	try {	
		$dbh = new PDO($DB['DSN_DEV'],$DB['DB_USER'], $DB['DB_PWD'],
			array( PDO::ATTR_PERSISTENT => false));
		# 錯誤的話, 就不做了
		$dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$p = $dbh->prepare("select no,url,`desc` from pdf_info where created_at >= ?");
		$p2 = $dbh->prepare("update `pdf_info` set `url`=concat(`url`,',',?), updated_at=now() where no=?");
		$p3 = $dbh->prepare("update `pdf_info` set `url`=?, updated_at=now() where no=?");
		$p->execute(array($created_at));
		$resData = $p->fetchAll(PDO::FETCH_ASSOC);
		foreach($resData as $record)
		{
			$url = $pdf_pattern . $record['no'] . $pdf_tailer;
			$records = json_decode($web->GetWebService($url));
			if($records->Count >= 1)
			{
				foreach($records->Content as $item)
				{
					if(!isset($record['url']))
					{
						$p3->execute(array($item->PdfLocation,$record['no']));
						$record['url'] = $item->PdfLocation;
					}
					else
					{
						$p2->execute(array($item->PdfLocation,$record['no']));
						$record['url'] = $record['url'] . ',' . $item->PdfLocation;
					}
				}
			}
		}
		unset($resData);
	} catch (PDOException $e) {
		$errMsg = "Error: ".$e->getLine() . $e->getMessage() . "\n";
		die($errMsg);
	}
}

$check_time = new DateTime(date('Y-m-d 00:00:00'));
#$check_time = new DateTime(date('2014-03-22 00:00:00'));
$created_at = $check_time->format('Y-m-d H:i:s');
# 先掃
ScanAllTable($DB,$URL_PATTERN,$URL_TAILER,$created_at);
FetchPDFByTime($DB,$PDF_PATTERN,$PDF_TAILER,$created_at);
$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
	array( PDO::ATTR_PERSISTENT => false));
	# 錯誤的話, 就不做了
$dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$dbh2 = new PDO($DB['DSN_DEV'],$DB['DB_USER'], $DB['DB_PWD'],
	array( PDO::ATTR_PERSISTENT => false));
	# 錯誤的話, 就不做了
$dbh2->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$ItemInfo = new ItemInfo($dbh);
$PDFInfo = new PDFInfo($dbh2);
$DataArray = $PDFInfo->getAllPDF();
$errorMsg = array();
foreach($DataArray as $item)
{
	$librick_array = $ItemInfo->getLibrickID($item->no."-1");
	$found = false;
	if($librick_array != null)
	{
		foreach($librick_array as $librick_item)
		{
			$ItemInfo->updateItemURL($librick_item->id,$item->url);
			$found = true;
		}
	}
	if($found)
	{
		$PDFInfo->updateFound($item->no);
	}
	else
	{
		$errorMsg[] = $item->no . ' ' . $item->desc;
	}
}
if(count($errorMsg) != 0)
{
	$title = '樂高公司PDF對應失敗通知';
	$notify = new SendNotify($debug);
	$notify->pushList($title,$errorMsg);
	unset($notify);
}
exit;
# 後抓
#FetchPDF($DB,$PDF_PATTERN,$PDF_TAILER);	// All Do
#FetchPDFByTime($DB,$PDF_PATTERN,$PDF_TAILER,'2014-3-9 0:0:0')

?>
