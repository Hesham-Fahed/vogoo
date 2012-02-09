<?php
/**
 * VogooException 
 * 
 * @package 
 * @version $id$ Last Change: 2012/02/08 20:21:30.
 * @copyright longkey1. all rights reserved.
 * @author longkey1 <longkey1@gmail.com> 
 * @license longkey1. {@link http://longkey1.net/}
 */
class VogooException {};
/**
 * Vogoo 
 * 
 * @package 
 * @version $id$ Last Change: 2012/02/08 20:21:30.
 * @copyright longkey1. all rights reserved.
 * @author longkey1 <longkey1@gmail.com> 
 * @license longkey1. {@link http://longkey1.net/}
 */
class Vogoo 
{
	protected $_PDO;
	protected $_Connected;

	public static $VG_THRESHOLD_NR_COMMON_RATINGS = 30;
	public static $VG_THRESHOLD_MULT = 2;
	public static $VG_THRESHOLD_RATING =  0.66;
	public static $VG_COST = 5.0;
	public static $VG_NOT_INTERESTED = -1.0;
	public static $VG_DIRECT_LINKS = false;
	public static $VG_DIRECT_SLOPE = false;

	/**
	 * __construct 
	 * 
	 * @param mixed $driver 
	 * @param mixed $host 
	 * @param mixed $dbname 
	 * @param mixed $user 
	 * @param mixed $password 
	 * @access public
	 * @return void
	 */
	public function __construct($driver, $host, $dbname, $user, $password) {
		// PDO
		$_PDO = new PDO("$driver:host=$host; dbname=$dbname", $user, $password);
	}

	/**
	 * memberNumRatings 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function memberNumRatings($params) {
		$defaultParams = array(
			'memberId' => null,
			'realRatings' => true,
			'notInterested' => false,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');
		}

		$sql = "SELECT "
			 . "  COUNT(*) AS number_of_ratings FROM vogoo_ratings "
			 . "WHERE "
			 . "  member_id = :memberId AND "
			 . "  category = :category ";

		// Filter real ratings/not interested information
		if ($realRatings === true) {
			if (!$notInterested) {
				$sql .= "AND rating >= 0.0 ";
			}

		// if not_interested is set to false, then the user is a weirdo ;)
		// don't handle this case
		} else {
			$sql .= "AND rating = self::$VG_NOT_INTERESTED";
		}

		$q = $_PDO->prepare($sql);
		$q->execute(array(':memberId' => $options['memberId'], ':categogy' => $options['categogy']));

		$row = $q->fetch(PDO::FETCH_ASSOC);
		$result = $row['number_of_ratings'];

		return $result;
	}

	/**
	 * memberAverageRating 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function memberAverageRating($params) {
		$defaultParams = array(
			'memberId' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');
		}

		$sql = "SELECT avg(rating) AS average "
			 . "FROM vogoo_ratings "
			 . "WHERE member_id = :memberId "
			 . "AND category = :category "
			 . "AND rating >= 0.0 ";
		$q = $_PDO->prepare($sql);
		$q->execute(array(':memberId' => $options['memberId'], ':categogy' => $options['categogy']));

		$row = $q->fetch(PDO::FETCH_ASSOC);
		$result = $row['average'];
		if (is_null($result)) {
			$result = 0.0;
		}
		return $result;
	}

	/**
	 * memberRatings 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function memberRatings($params) {
		$defaultParams = array(
			'memberId' => null,
			'orderByDate' => false,   // ASC | DESC | false
			'orderByRating' => false, // ASC | DESC | false
			'realRatings' => true,
			'notInterested' => false,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');
		}

		$sql = "SELECT product_id, rating, ts "
			 . "FROM vogoo_ratings "
			 . "WHERE member_id = :memberId "
			 . "AND category = :category ";

		// Filter real ratings/not interested information
		if ($options['realRatings'] === true) {
			if ($options['notInterested'] === false) {
				$sql .= "AND rating >= 0.0 ";
			}
		} else {
			$sql .= "AND rating = self::$VG_NOT_INTERESTED";
		}

		if (in_array(strtoupper($options['orderbyDate']), array('ASC', 'DESC'))) {
			$sql .= "ORDER BY ts " . strtoupper($options['orderbyDate']) . " ";

		} elseif (in_array(strtoupper($options['orderbyRating']), array('ASC', 'DESC'))) {
			$sql .= "ORDER BY rating " . strtoupper($options['orderbyRating']) . " ";
		}

		$q = $_PDO->prepare($sql);
		$q->execute(array(':memberId' => $options['memberId'], ':categogy' => $options['categogy']));
		$result = $q->fetchAll();

		return $result;
	}

	/**
	 * deleteMember 
	 * 
	 * @param mixed $memberId 
	 * @access public
	 * @return void
	 */
	public function deleteMember($memberId) {
		if (!isset($memberId) || !is_numeric($memberId)) {
			throw new VogooException('Invalid parameter : memberId');
		}

		$sql = "DELETE "
			 . "FROM vogoo_ratings "
			 . "WHERE member_id = :memberId ";

		$q = $_PDO->prepare($sql);
		$q->execute(array(':memberId' => $memberId));

		return true;
	}

	/**
	 * productNumRatings 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function productNumRatings($params) {
		$defaultParams = array(
			'productId' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		}

		$sql = "SELECT count(*) AS number_of_ratings "
			 . "FROM vogoo_ratings "
			 . "WHERE product_id = :productId "
			 . "AND rating >= 0.0 "
			 . "AND category = :category ";

		$q = $_PDO->prepare($sql);
		$q->execute(array(':productId' => $options['productId'], ':categogy' => $options['categogy']));

		$row = $q->fetch(PDO::FETCH_ASSOC);
		$result = $row['number_of_ratings'];

		return $result;
	}

	/**
	 * productAverageRating 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function productAverageRating($params) {
		$defaultParams = array(
			'productId' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		$sql = "SELECT avg(rating) AS average "
			 . "FROM vogoo_ratings "
			 . "WHERE product_id = :productId "
			 . "AND category = :category "
			 . "AND rating >= 0.0 ";

		$q = $_PDO->prepare($sql);
		$q->execute(array(':productId' => $options['productId'], ':categogy' => $options['categogy']));

		$row = $q->fetch(PDO::FETCH_ASSOC);
		$result = $row['average'];
		if (is_null($result)) {
			$result = 0.0;
		}
		return $result;
	}

	/**
	 * productRatings 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function productRatings($params) {
		$defaultParams = array(
			'productId' => null,
			'orderByDate' => false,   // ASC | DESC | false
			'orderByRating' => false, // ASC | DESC | false
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		}

		$sql = "SELECT member_id, rating, ts "
			 . "FROM vogoo_ratings "
			 . "WHERE product_id = :productId "
			 . "AND rating >= 0.0 "
			 . "AND category = :category ";

		if (in_array(strtoupper($options['orderbyDate']), array('ASC', 'DESC'))) {
			$sql .= "ORDER BY ts " . strtoupper($options['orderbyDate']) . " ";

		} elseif (in_array(strtoupper($options['orderbyRating']), array('ASC', 'DESC'))) {
			$sql .= "ORDER BY rating " . strtoupper($options['orderbyRating']) . " ";
		}

		if ( !($result = $this->db->sql_query($sql)) )
		{
			return false;
		}

		$q = $_PDO->prepare($sql);
		$q->execute(array(':memberId' => $options['memberId'], ':categogy' => $options['categogy']));
		$result = $q->fetchAll();

		return $result;
	}

	/**
	 * deleteProduct 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function deleteProduct($params) {
		$defaultParams = array(
			'memberId' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		}

		$sql = "DELETE FROM vogoo_ratings "
			 . "WHERE product_id = :productId "
			 . "AND category = :categoryId ";

		$q = $_PDO->prepare($sql);
		$q->execute(array(':productId' => $options['productId'], ':categogy' => $options['categogy']));

		return true;
	}

	/**
	 * getRating 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function getRating($params) {
		$defaultParams = array(
			'memberId' => null,
			'productId' => null,
			'notInterested' => false,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');

		} elseif(!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		}

		$sql = "SELECT rating, ts "
			 . "FROM vogoo_ratings "
			 . "WHERE member_id = :memberId "
			 . "AND product_id = :product_id "
			 . "AND category = :category ";

		if ($options['notInterested'] === false) {
			$sql .= "AND rating >= 0.0 ";
		}

		$q = $_PDO->prepare($sql);
		$q->execute(array(
			':memberId' => $options['memberId'],
			':productId' => $options['productId'],
			':categogy' => $options['categogy']
		));
		$rowCount = $q->rowCount();

		if ($rowCount > 1) {
			$msg = sprintf('Invalid ratings Data [memberId=%d, productId=%d]', $options['memberId'], $options['productId']);
			throw new VoGooException($msg);
		} elseif ($rowCount === 0) {
			return false;
		}

		$result = $q->fetch(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * setRating 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function setRating($params) {
		$defaultParams = array(
			'memberId' => null,
			'productId' => null,
			'rating' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');
		} elseif(!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		} elseif (($optipns['rating'] < 0.0 && $rating !== VG_NOT_INTERESTED) || ($options['rating'] > 0.0)) {
			throw new VogooException('Invalid parameter : rating');
		}

		$sql = "SELECT rating "
			 . "FROM vogoo_ratings "
			 . "WHERE member_id = :memberId "
			 . "AND product_id = :product_id "
			 . "AND category = :category ";

		$q = $_PDO->prepare($sql);
		$q->execute(array(
			':memberId' => $options['memberId'],
			':productId' => $options['productId'],
			':categogy' => $options['categogy']
		));

		$opt = array(
			'memberId' => $options['memberId'],
			'productId' => $options['productId'],
			'categogy' => $options['categogy'],
			'rating' => $options['rating'],
		);
		$rowCount = $q->rowCount();
		if ($rowCount === 1) {
			$row = $q->fetch(PDO::FETCH_ASSOC);
			$opt['previous'] = $row['rating'];
			if (self::$VG_DIRECT_LINKS) {
				$this->setDirectLinks($opt);
			}
			if (self::$VG_DIRECT_SLOPE) {
				$this->setDirectSlope($opt);
			}
			$sql = "UPDATE vogoo_ratings "
				 . "SET rating = :rating, ts = NOW() "
				 . "WHERE member_id = :memberId "
				 . "AND product_id = :productId "
				 . "AND category = :category ";

			$q = $_PDO->prepare($sql);

			$result = $q->execute(array(
				':memberId' => $options['memberId'],
				':productId' => $options['productId'],
				':categogy' => $options['categogy']
			));
			if ($result === false) {
				throw new VoGooException('Update error : vogoo_ratings');
			}

		} else if ($rowCount === 0) {
			$opt = array(
				'memberId' => $options['memberId'],
				'productId' => $options['productId'],
				'categogy' => $options['categogy'],
				'rating' => $options['rating'],
				'previous' => -1.0,
			);
			if (self::$VG_DIRECT_LINKS) {
				$this->setDirectLinks($opt);
			}
			if (self::$VG_DIRECT_SLOPE) {
				$this->setDirectLinks($opt);
			}
			$sql = "INSERT INTO vogoo_ratings "
				 . "(member_id, product_id, category, rating, ts) "
				 . "VALUES (:memberId, :productId, :category, :rating, NOW()) ";

			$q = $_PDO->prepare($sql);

			$result = $q->execute(array(
				':memberId' => $options['memberId'],
				':productId' => $options['productId'],
				':categogy' => $options['categogy'],
				':rating' => $options['rating'],
			));
			if ($result === false) {
				throw new VoGooException('Insert error : vogoo_ratings');
			}

		} else {
			$msg = sprintf('Invalid ratings Data [memberId=%d, productId=%d]', $options['memberId'], $options['productId']);
		}
		return true;
	}

	/**
	 * automaticRating 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function automaticRating($params) {
		$defaultParams = array(
			'memberId' => null,
			'productId' => null,
			'purchase' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');
		} elseif(!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		} elseif ($options['purchase']) {
			$opt = array(
				'memberId' => $options['memberId'],
				'productId' => $options['productId'],
				'rating' => 1.0,
				'category' => $options['category'],
			);
			return $this->setRating($opt);
		} else {
			// A click
			$opt = array(
				'memberId' => $options['memberId'],
				'productId' => $options['productId'],
				'notInterested' => false,
				'category' => $options['category'],
			);
			$result = $this->getRating($opt);
			if ($result === false) {
				$opt = array(
					'memberId' => $options['memberId'],
					'productId' => $options['productId'],
					'rating' => 0.7,
					'category' => $options['category'],
				);
				return $this->setRating($opt);

			} elseif ($result['rating'] < 1.0) {
				$opt = array(
					'memberId' => $options['memberId'],
					'productId' => $options['productId'],
					'rating' => $result['rating'] + 0.01,
					'category' => $options['category'],
				);
				return $this->setRating($opt);
			}
		}
	}

	/**
	 * setNotInterested 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function setNotInterested($params) {
		$defaultParams = array(
			'memberId' => null,
			'productId' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		$opt = array(
			'memberId' => $options['memberId'],
			'productId' => $options['productId'],
			'rating' => self::$VG_NOT_INTERESTED,
			'category' => $options['category'],
		);
		return $this->setRating($opt);
	}

	/**
	 * deleteRating 
	 * 
	 * @param mixed $params 
	 * @access public
	 * @return void
	 */
	public function deleteRating($params) {
		$defaultParams = array(
			'memberId' => null,
			'productId' => null,
			'category' => 1,
		);
		$options = array_merge($defaultParams, $params);

		if (!isset($options['memberId']) || !is_numeric($options['memberId'])) {
			throw new VogooException('Invalid parameter : memberId');

		} elseif(!isset($options['productId']) || !is_numeric($options['productId'])) {
			throw new VogooException('Invalid parameter : productId');
		}

		if (self::$VG_DIRECT_LINKS || self::$VG_DIRECT_SLOPE) {
			$sql = "SELECT rating "
				 . "FROM vogoo_ratings "
				 . "WHERE member_id = :memberId "
				 . "AND product_id = :productId "
				 . "AND category = :category ";
			$q = $_PDO->prepare($sql);
			$result = $q->execute(array(
				':memberId' => $options['memberId'],
				':productId' => $options['productId'],
				':categogy' => $options['categogy']
			));

			if ($result === false) {
				$msg = sprintf('Delete error - [table=vogoo_ratings, memberId=%d, productId=%d]', $options['memberId'], $options['productId']);
				throw new VoGooException($msg);
			}

			$rowCount = $q->rowCount();
			if ($rowCount === 1) {
				$row = $q->fetch(PDO::FETCH_ASSOC);
				$opt = array(
					'memberId' => $options['memberId'],
					'productId' => $options['productId'],
					'categogy' => $options['categogy'],
					'rating' => -1.0,
					'previous' => $row['rating'],
				);
				if (self::$VG_DIRECT_LINKS) {
					$this->setDirectLinks($opt);
				}
				if (self::$VG_DIRECT_SLOPE) {
					$this->setDirectSlope($opt);
				}
			}
		}
		$sql = "DELETE FROM vogoo_ratings "
			 . "WHERE member_id = :memberId "
			 . "AND product_id = :product_id "
			 . "AND category = :category ";
		$q = $_PDO->prepare($sql);
		$result = $q->execute(array(
			':memberId' => $options['memberId'],
			':productId' => $options['productId'],
			':categogy' => $options['categogy']
		));

		if ($result === false) {
			$msg = sprintf('Delete error - [table=vogoo_ratings, memberId=%d, productId=%d]', $options['memberId'], $options['productId']);
			throw new VoGooException($msg);
		}

		return true;
	}
}
