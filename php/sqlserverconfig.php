<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../configs');
$dotenv->safeLoad();

$keyContents = file_get_contents(__DIR__ . '/../../configs/key');
$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);
//$secret = \Defuse\Crypto\Crypto::encrypt('192.168.0.21', $key);
//print_r($secret);

class MsDBConnection{
	
	public $connection;
	
	function __construct($configno, $dbname){
		

		$keyContents = file_get_contents(__DIR__ . '/../../configs/key');
		$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);

		$servername = \Defuse\Crypto\Crypto::decrypt($_ENV['SAP_NAME'], $key);
		$username = \Defuse\Crypto\Crypto::decrypt($_ENV['SAP_USERNAME'], $key); 
		$password = \Defuse\Crypto\Crypto::decrypt($_ENV['SAP_PASSWORD'], $key);

		$servers[] = [$servername, $username, $password];

		$servername = \Defuse\Crypto\Crypto::decrypt($_ENV['DVPRO_NAME'], $key);
		$username = \Defuse\Crypto\Crypto::decrypt($_ENV['DVPRO_USERNAME'], $key);
		$password = \Defuse\Crypto\Crypto::decrypt($_ENV['DVPRO_PASSWORD'], $key);
		$servers[] = [$servername, $username, $password];

		$charset =  "UTF-8";

		$connectionOptions = array("Database"=>$dbname, "CharacterSet" => $charset, "UID"=>$servers[$configno][1], "PWD"=>$servers[$configno][2], "TrustServerCertificate"=>true);
		
	  try{
		$this->connection = sqlsrv_connect($servers[$configno][0], $connectionOptions);
		
		if($this->connection)
		{
			//echo '<script>alert("connected")</script>';
		}
		else
		{
			die( print_r( sqlsrv_errors(), true));
		}
	  }
	  catch(Exception $e){
	    if (!$this->connection){
	      error_log($e->getMessage());
	  	  die($e->getMessage());
		}
	  }
	}
	
	function __destruct(){
		sqlsrv_close($this->connection);
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
	
	function returnSingle($sql='')
	{
		$retval = "";

		$stmt = sqlsrv_query($this->connection, $sql);
		$res = sqlsrv_fetch_array($stmt);
		$retval = $res[0];

		return $retval;
	}

	function PreparedInsertSingle($sql,$exploderstr,$paramvals,$paramtype)
	{
		$retval = '0';
		$stmt = $this->connection->prepare($sql);
		if(!empty($paramvals)){
			if(!empty($exploderstr))
			{
				$paramvals = explode($exploderstr,$paramvals);
				$stmt->bind_param($paramtype,...$paramvals);
			}
			else
			{
				$stmt->bind_param($paramtype,$paramvals);
			}
		}
		
		$stmt->execute();
		$retval = $stmt->affected_rows;
		return $retval;
	}

	function returnSearchedEq($toSearch)
	{
		$sql = "SELECT PrcCode, PrcName FROM [SAPB1-SERVER].[Ravago_Live].[dbo].[OPRC] oprc
		        INNER JOIN [SAPB1-SERVER].[Ravago_Live].[dbo].[ODIM] odim 
				ON odim.DimCode = oprc.DimCode where oprc.PrcName like ? and 
				odim.DimDesc = 'Equipment'";

		$stmt = sqlsrv_prepare($this->connection, $sql, array("'%".$toSearch."%'"));

		sqlsrv_execute($stmt);
		
		while($row = sqlsrv_fetch_array($stmt))
		{
			$response[] = array("searchEq"=>$row['PrcName'], "equipId"=>$row['PrcCode']);
		}

		return json_encode($response);
	}

	function PreparedGetFields($query, $paramVals)
	{
		$stmt = sqlsrv_prepare($this->connection, $query, $paramVals);

		if(sqlsrv_execute($stmt)) 
		{
			$response = [];

			while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC))
			{
				$response[] = $row;
			}

			return json_encode($response);
		}
	}

	function PreparedGetField($sql, $paramvals)
	{
		$retval = "";

		$stmt = sqlsrv_prepare($this->connection, $sql, array($paramvals));

		if($stmt === false)
		{
			die( print_r(sqlsrv_errors(), true));  
		}
		else
		{
			$res = sqlsrv_execute($stmt);
			$res = sqlsrv_fetch_array($stmt);

			if($res){
				$retval = $res[0];
			}
			else{
				die( print_r(sqlsrv_errors(), true));  
			}
		}

		return $retval;
	}

	function PopulateCombobox($sql, $withvalue, $noselected)
	{
		$stmt = sqlsrv_query($this->connection, $sql);

		if($noselected == 1)
		{
			echo '<option disabled selected value> -- select an option -- </option>';
		}
		
		if($withvalue == 1)
		{
			while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC))
			{
				echo '<option value="'.$row[1].'">'. $row[0]. '</option>';
			}
		}
		else
		{
			while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC))
			{
				echo '<option>'. $row[0]. '</option>';
			}
		}
	}

	function returnSearchedBP($toSearch)
	{
		$sql = "SELECT CardName, CardCode FROM [SAPB1-SERVER].[Ravago_Live].[dbo].[OCRD] where CardName like ?";

		$stmt = sqlsrv_prepare($this->connection, $sql, array("%".$toSearch."%"));

		sqlsrv_execute($stmt);
		
		while($row = sqlsrv_fetch_array($stmt))
		{
			$response[] = array("BPName"=>$row['CardName'], "BPId"=>$row['CardCode']);
		}

		return json_encode($response);
	}

	function PreparedGetForAutocomplete($query, $val, $assocName)
	{
		$stmt = sqlsrv_prepare($this->connection, $query, array("%".$val."%"));

		sqlsrv_execute($stmt);
		
		while($row = sqlsrv_fetch_array($stmt,  SQLSRV_FETCH_NUMERIC))
		{
			$response[] = array($assocName=>$row[0]);
		}

		return json_encode($response);
	}
}
?>