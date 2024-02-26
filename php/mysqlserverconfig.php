<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../configs');
$dotenv->safeLoad();

/*$keyContents = file_get_contents(__DIR__ . '/../../configs/key');
$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);
$secret = \Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
$secret = \Defuse\Crypto\Crypto::encrypt('localhost', $key);
print_r($secret);*/

class MyDBConnection{ 
	
	public $connection;
	
	function __construct($configno, $dbname) //0 - dbuser , 1 - ops
	{

		$keyContents = file_get_contents(__DIR__ . '/../../configs/key');
		$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);

        $servername = \Defuse\Crypto\Crypto::decrypt($_ENV['DBUSER_NAME'], $key);
		$username = \Defuse\Crypto\Crypto::decrypt($_ENV['DBUSER_USERNAME'], $key);
		$password = \Defuse\Crypto\Crypto::decrypt($_ENV['DBUSER_PASSWORD'], $key);

		$servers = [array($servername, $username, $password)];

		$servername = \Defuse\Crypto\Crypto::decrypt($_ENV['OPS_NAME'], $key);
		$username = \Defuse\Crypto\Crypto::decrypt($_ENV['OPS_USERNAME'], $key);
		$password = \Defuse\Crypto\Crypto::decrypt($_ENV['OPS_PASSWORD'], $key); 

		//print_r($servername . ' ' . $username . ' ' . $password);

		$servers[] = array($servername, $username, $password);

		try
		{
			$this->connection = new mysqli($servers[$configno][0], $servers[$configno][1], 
			$servers[$configno][2], $dbname);
			$this->connection->set_charset("utf8mb4");
		}
		catch(Exception $e)
		{
			if (!$this->connection)
			{
				error_log($e->getMessage());
				die($e->getMessage());
			}
		}
	}
		
	function __destruct()
	{
		mysqli_close($this->connection);
	}

	function GenerateSalesTicketNo(){
		$stn = ""; //Sales Ticket No

		$query = "SELECT MAX(CAST(SUBSTR(inquiryNo, 3) as unsigned)) from INQR";
		$maxRequestId = $this->PreparedGetField($query,'','');
		
		$query = "SELECT date_format(now(),'%y%m%d');";
		$pendingTransId = $this->PreparedGetField($query,'','');
		
		if(strlen($maxRequestId) == 9){

			if(substr($maxRequestId,0,6) == $pendingTransId){
				$stn = $maxRequestId + 1;
			}
			else{
				$stn = $pendingTransId.'001';	
			}
		}
		else{
			$stn = $pendingTransId.'001';
		}

		return 'ST'.$stn;
	}

	function TransactPreparedQuery($queries, $paramvalses, $paramtype)
	{
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		
		mysqli_begin_transaction($this->connection);

		try
		{ 
			for($i = 0; $i < count($queries); $i++)
			{
				$query = $queries[$i];
				$stmt = $this->connection->prepare($query);

				for($j = 0 ; $j < count($paramvalses[$i]); $j++)
				{
					if(!empty($paramvalses[$i][$j]))
					{
						$stmt->bind_param($paramtype[$i], ...$paramvalses[$i][$j]);
						$stmt->execute();						
						$stmt->free_result();                   
					}
				}
			}

			if(mysqli_commit($this->connection))
			{
				return "1";
			}
		}
		catch(mysqli_sql_exception $exception)
		{
			mysqli_rollback($this->connection);
			throw $exception;
		}
	}
	
	function hasRow($sql,$fieldvalue,$fieldvaluetype)
	{
		$retval = false;
		$result = $this->connection->prepare($sql);
		$result->bind_param($fieldvaluetype,$fieldvalue);
		$result->execute();
		$row = $result->get_result();
		
		if(mysqli_num_rows($row) > 0){
			$retval = true;
		}
		
		return $retval;
	}
	
	function returnSingle($sql='', $exploderstr='', $fieldvalue='', $valuetype='')
	{
		$retval = "";
		
		$result = $this->connection->prepare($sql);
		if(!empty($fieldvalue)){
		  if(!empty($exploderstr)){
		    $fieldvalue = explode($exploderstr,$fieldvalue);
			$result->bind_param($valuetype,...$fieldvalue);
		  }
		  else{
            $result->bind_param($valuetype,$fieldvalue);    
		  }
		}
		
		$result->execute();
		$row = $result->get_result();
		
		if(mysqli_num_rows($row) > 0){
		  $row = $row->fetch_row();
		  $retval = $row[0];
		}
		
		return $retval;
	}

	function PreparedInsertSingle($sql,$paramvals,$paramtype)
	{
		$retval = "0";
		$stmt = $this->connection->prepare($sql);
		if(!empty($paramvals)){
			$stmt->bind_param($paramtype,...$paramvals);
		}
		
		$stmt->execute();
		$retval = $stmt->affected_rows;
		return $retval;
	}

	function PreparedGetFields($sql, $paramvals, $paramtype)
	{
		$stmt = $this->connection->prepare($sql);
		if(!empty($paramvals)){
			$stmt->bind_param($paramtype,...$paramvals);
		}
		
		$stmt->execute();
		$result = $stmt->get_result();

		$response = [];

		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$response[] = $row; 
		}

		return json_encode($response);
	}

	function PreparedGetField($sql, $paramvals, $paramtype)
	{
		$retval = "";
		
		$result = $this->connection->prepare($sql);
		if(!empty($paramvals)){
			$result->bind_param($paramtype,...$paramvals);
		}
		
		$result->execute();
		$row = $result->get_result();
		
		if(mysqli_num_rows($row) > 0){
		  $row = $row->fetch_row();
		  $retval = $row[0];
		}

		return $retval;
	}

	function PreparedGetForAutocomplete($sql, $exploderstr, $paramvals, $paramtype, $ret)
	{
		$stmt = $this->connection->prepare($sql);
		if(!empty($paramvals))
		{
		  if(!empty($exploderstr))
		  {
		    $paramvals = explode($exploderstr, $paramvals);
			$stmt->bind_param($paramtype,...$paramvals);
		  }
		  else
		  {
            $stmt->bind_param($paramtype,$paramvals);    
		  }
		}
		
		$stmt->execute();
		$result = $stmt->get_result();

		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$response[] = array($ret=>$row[0]);
		}

		//var_dump($result->fetch_all(MYSQLI_NUM));

		return json_encode($response);
	}

	function selectMax($tableName, $column)
	{
		$query = "SELECT MAX(".$column.") FROM ".$tableName;

		$result = $this->connection->prepare($query);
		$result->execute();
		$row = $result->get_result();
		
		if(mysqli_num_rows($row) > 0){
		  $row = $row->fetch_row();
		  return $row[0];
		}

		return 0;
	}

	function PopulateCombobox($query, $withvalue, $noselected)
	{
		if($result = mysqli_query($this->connection, $query))
		{
			if($noselected == 1)
			{
				echo '<option disabled selected value> -- select an option -- </option>';
			}

			if($withvalue == 1)
			{
				while($row = $result->fetch_array(MYSQLI_NUM))
				{
					echo "<option value='".$row[1]."'>". $row[0]. '</option>';
				}
			}
			else
			{
				while($row = $result->fetch_array(MYSQLI_NUM))
				{
					echo '<option>'. $row[0]. '</option>';
				}
			}
		}
		$result->free_result();
	}

	function GetEmployeeByDesg($desg, $search)
	{
		$query = "select emp.displayName, emp.employeeId from dboperation.emp inner join dboperation.empdsg edsg ON 
		emp.employeeId = edsg.employeeId inner join dboperation.dsgntn dsg ON edsg.designationCode = dsg.designationCode 
		where dsg.description= ? AND emp.displayName like ?";

		$stmt = $this->connection->prepare($query);
		$likeparam = "%".$search."%";
		$stmt->bind_param("ss", $desg, $likeparam);
		$stmt->execute();
		$result = $stmt->get_result();

		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$response[] = array("empName"=>$row[0], "empId"=>$row[1]);
		}
		return json_encode($response);
	}

	function CreateProject($projectId, $deploymentDate, $salesAcctHolder, $salesQuotNum, $client, $site, 
	$numDuration, $labelDuration, $remarks, $tblManpower, $tblEquipment, $userId)
	{
		$duration = $numDuration. ',' .$labelDuration;
		$retval = "0";

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		
		mysqli_begin_transaction($this->connection);

		try
		{
			//TABLE PROJECT
			$query = "INSERT INTO proj(projId, dateAdded, dateDeployment, cardCode, site, duration, salesAcctHolderId, 
			quotationNo, remarks, encoder) values(?, now(), ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $this->connection->prepare($query);
			$stmt->bind_param('sssssssss', $projectId, $deploymentDate, $client, $site, $duration, 
			$salesAcctHolder, $salesQuotNum, $remarks, $userId) ;
			$stmt->execute();

			//TABLE PROJECT REQUESTED EQUIPMENT
			$query = "INSERT INTO prjrqeq(projId, eqNo, equipmentGrpCode, quantity) 
			values(?,?,?,?)".str_repeat(",(?,?,?,?)", count($tblEquipment) -1);

			$stmt = $this->connection->prepare($query);
			$types = str_repeat("sisi", count($tblEquipment));
			$values = array_merge(...$tblEquipment);

			$stmt->bind_param($types, ...$values);
			$stmt->execute();

			//TABLE PROJECT REQUESTED EMPLOYEE
			$query = "INSERT INTO prjrqem(projId, eqNo, designationCode) 
			values(?,?,?)".str_repeat(",(?,?,?)", count($tblManpower) -1);
			$stmt = $this->connection->prepare($query);
			$types = str_repeat("sis", count($tblManpower));
			$values = array_merge(...$tblManpower);
			$stmt->bind_param($types, ...$values);
			//return implode(",",$values);
			$stmt->execute();
			/*
			if(mysqli_commit($this->connection))
			{
				return "1";
			}*/

			//dito po
		}
		catch(mysqli_sql_exception $exception)
		{
			mysqli_rollback($this->connection);
			$retval = "0";
			throw $exception;
		}


		return $retval;
	}
}
?>