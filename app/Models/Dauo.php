<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class Dauo extends Model
{
	protected $DBGroup              = 'default';
	protected $table                = 'usn';
	protected $primaryKey           = 'id';

	protected $allowedFields        = ['email', 'password', 'username'];



	function getEmail($email)
	{
		$builder = $this->table("tbl_users");
		$data = $builder->where("email", $email)->first();
		if (!$data) {
			throw Exception("Data Tidak Ditemukan");
			return;
		}
		return $data;
	}

	function tampil_data($table)
	{
		$db = db_connect();
		$query = $db->query('select * from ' . $table);

		if ($query) {
			$row = $query->getNumRows();
			return $row;
		} else {
			return false;
		}
	}

	public function ambilin2parameter($table, $arg1, $params1, $arg2, $params2)
	{
		$dataSegmentations = $this->db->query(
			"SELECT * FROM $table WHERE $arg1 = ? AND $arg2 = ?",
			[$params1, $params2]
		);

		return $dataSegmentations->getResultArray(); // ✅ CI4
	}


	function input_data($data, $tables)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$query = $builder->insert($data);

		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}


	function updateuser($data, $tables, $params1, $arg1)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$builder->set($data);
		$builder->update();

		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}

	function update_data($data, $tables, $params1, $arg1)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$builder->set($data);
		$builder->update();

		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}
	function update_data2params($data, $tables, $params1, $arg1, $params2, $arg2)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$builder->where($params2, $arg2);
		$builder->set($data);
		$builder->update();

		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}

	function getdatapublic($tables, $params1, $arg1, $params2, $arg2)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$builder->where($params2, $arg2);
		$query   = $builder->get();
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return $tables;
		}
	}


	function getdatawhere($tables)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$likeCriteria2 = "(status = 'sudahbayar'
		OR  status = 'belumbayar'
	  OR  status = 'declined')";
		$builder->where($likeCriteria2);
		$query   = $builder->get();
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return array();
		}
	}
	function getdatawhere2params($tables, $params1, $arg1, $params2, $arg2)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$builder->where($params2, $arg2);

		$query   = $builder->get();
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return array();
		}
	}

	function getdatawhere1plain($tables, $params1, $arg1)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$query   = $builder->get();
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return array();
		}
	}

	function getdatawhere1params($tables, $params1, $arg1)
	{
		$db = db_connect();
		$builder = $db->table($tables);
		$builder->where($params1, $arg1);
		$likeCriteria2 = "(status = 'sudahbayar'
		OR  status = 'belumbayar'
	  OR  status = 'declined')";
		$builder->where($likeCriteria2);
		$query   = $builder->get();
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return array();
		}
	}

	function getdata1params($tables, $params1, $arg1)
	{
		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' . esc($params1) . ' = "' . esc($arg1) . '"');

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}

	function getdatadistinct($tables)
	{
		$db = db_connect();
		$query = $db->query('SELECT  * FROM ' . $tables);

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}

	function getdata1paramsbl($tables, $params1, $arg1)
	{
		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE MONTH(' . $params1 . ') = "' . esc($arg1) . '"');

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}

	public function getdata2paramsbl($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . $tables .
			' WHERE ' . $params1 . ' = "' . $arg1 . '" AND MONTH(' .
			$params2 . ') = "' . $arg2 . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	function getdata1paramsth($tables, $params1, $arg1)
	{
		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . $tables . ' WHERE YEAR(' . $params1 . ') = "' . $arg1 . '"');

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}

	public function getdata2paramsth($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . $tables .
			' WHERE ' . $params1 . ' = "' . $arg1 . '" AND YEAR(' .
			$params2 . ') = "' . $arg2 . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	function getdata1paramsless($tables, $params1, $arg1)
	{
		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' .
			esc($params1) . ' <= "' . esc($arg1) . '"');

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}

	public function getdata2paramsless($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' . esc($params1) . ' = "' . esc($arg1) . '" AND ' .
			esc($params2) . ' <= "' . esc($arg2) . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}


	function getdata($tables)
	{
		$db = db_connect();
		$query = $db->query('select * from ' . esc($tables));

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}



	public function hapus($tables, $params1, $arg1)
	{

		$db = db_connect();
		$query = $db->query('DELETE  FROM ' . $tables . ' WHERE ' . $params1 . ' = "' . $arg1 . '"');
		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}
	public function hapus2paramminor($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('DELETE  FROM ' . esc($tables) . ' WHERE ' . esc($params1) .
			' = "' . esc($arg1) . '" AND ' . esc($params2) . ' != "' . esc($arg2) . '"');
		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}

	public function hapus2params($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('DELETE  FROM ' . esc($tables) . ' WHERE ' . esc($params1) .
			' = "' . esc($arg1) . '" AND ' . esc($params2) . ' = "' . esc($arg2) . '"');
		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}

	public function hapus3params($tables, $params1, $arg1, $params2, $arg2, $params3, $arg3)
	{

		$db = db_connect();
		$query = $db->query('DELETE  FROM ' . esc($tables) . ' WHERE ' . esc($params1) .
			' = "' . esc($arg1) . '" AND ' . esc($params2) . ' = "' . esc($arg2) . '" AND ' . esc($params3) . ' = "' . esc($arg3) . '"');
		if ($db->affectedRows() > 0) {
			return "sukses"; // to the controller
		} else {
			return "gagal";
		}
	}

	public function amdriver($tables, $params1, $arg1)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' . esc($params1) . ' = "' . "$arg1" . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}
	public function getuser1params($tables, $params1, $arg1)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' .
			esc($params1) . ' != "' . esc($arg1) . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}
	public function getuser1paramsnot($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' .
			esc($params1) . ' != "' . esc($arg1) . '" AND ' .	esc($params2) . ' != "' . esc($arg2) . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}
	public function getuser2params($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' .
			esc($params1) . ' != "' . esc($arg1) . '" AND ' . esc($params2) . ' != "' . esc($arg2) . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	public function getsomething($arg1, $params1, $tables, $yangdicari)
	{

		$db = db_connect();
		$dataSegmentations = $db->query("SELECT $yangdicari as pt from $tables where $arg1='$params1' ;");
		$ret = $dataSegmentations->getresult();

		return $ret;
	}
	public function getsomethinglast($arg1, $params1, $tables, $yangdicari)
	{

		$db = db_connect();
		$dataSegmentations = $db->query("SELECT $yangdicari as pt from $tables where $arg1='$params1' ORDER BY id DESC LIMIT 1;");
		$ret = $dataSegmentations->getresult();

		return $ret;
	}

	public function getdata2paramsor($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) . ' = "' . esc($arg1) . '" OR ' .
			esc($params2) . ' = "' . esc($arg2) . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}
	public function getdataongoing($tables, $params1, $arg1, $params2, $arg2, $params3, $arg3)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . $tables .
			' WHERE ' . $params1 . ' = "' . $arg1 . '" AND ' .
			$params2 . ' != "' . $arg2 . '" AND ' .
			$params3 . ' != "' . $arg3 . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	public function getdata2params($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . $tables .
			' WHERE ' . $params1 . ' = "' . $arg1 . '" AND ' .
			$params2 . ' = "' . $arg2 . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}



	public function getdata2paramslike($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) . ' = "' . esc($arg1) . '" AND ' .
			esc($params2) . ' like "%' . $arg2 . '%"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	public function getdata2paramslikeorder($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) . ' = "' . esc($arg1) . '" AND ' .
			esc($params2) . ' like "%' . $arg2 . '%" ORDER BY status ASC, CAST(stok AS int) DESC');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	function getdataorder($tables)
	{
		$db = db_connect();
		$query = $db->query('select * from ' . $tables . ' ORDER BY status ASC, CAST(stok AS int) DESC');

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}
	public function getdata1paramslikeorder($tables, $params1, $arg1)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) . ' like "%' . $arg1 . '%" ORDER BY status ASC, CAST(stok AS int) DESC');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	function getdata1paramsorder($tables, $params1, $arg1)
	{
		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) . ' WHERE ' . esc($params1) . ' = "' . esc($arg1) . '" ORDER BY status ASC , CAST(stok AS int) DESC');

		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return false;
		}
	}

	public function getdata2paramsorder($tables, $params1, $arg1, $params2, $arg2)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . $tables .
			' WHERE ' . $params1 . ' = "' . $arg1 . '" AND ' .
			$params2 . ' = "' . $arg2 . '" ORDER BY status ASC, CAST(stok AS int) DESC');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}
	public function getdata3paramslikeorder($tables, $params1, $arg1, $params2, $arg2, $params3, $arg3)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) .
			' = "' . esc($arg1) . '" AND ' .
			esc($params2) . ' = "' . esc($arg2) . '" AND ' . $params3 . ' like "%' . $arg3 . '%" ORDER BY status ASC, CAST(stok AS int) DESC');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	public function getdata1paramslike($tables, $params1, $arg1)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) . ' like "%' . $arg1 . '%"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}


	public function getdata3params($tables, $params1, $arg1, $params2, $arg2, $params3, $arg3)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) .
			' = "' . esc($arg1) . '" AND ' .
			esc($params2) . ' = "' . esc($arg2) . '" AND ' . esc($params3) . ' = "' . esc($arg3) . '"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}

	public function getdata3paramslike($tables, $params1, $arg1, $params2, $arg2, $params3, $arg3)
	{

		$db = db_connect();
		$query = $db->query('SELECT * FROM ' . esc($tables) .
			' WHERE ' . esc($params1) .
			' = "' . esc($arg1) . '" AND ' .
			esc($params2) . ' = "' . esc($arg2) . '" AND ' . $params3 . ' like "%' . $arg3 . '%"');
		//var_dump($params1);
		if ($query) {
			$row = $query->getresult();
			return $row;
		} else {
			return "";
		}
	}
}