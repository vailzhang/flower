<?php
require '../../src/Sys/Functions/Common.php';

app ( 'Cli' )->run ();

$taskType = $_SERVER ["argv"] [1];

$task = new $taskType ();

$stopFile = __DIR__ . '/Sogou.stop';
if (file_exists ( $stopFile )) {
	unlink ( $stopFile );
}

while ( 1 ) {
	if (file_exists ( $stopFile )) {
		echo "stop {$taskType} \n";
		break;
	}
	$task->run ();
	sleep ( 1 );
}
exit ( 0 );
class fetch {
	protected $cContent;
	protected $curl;
	public function __construct() {
		$this->cContent = new \Sys\Mongo\Collection ( 'sogou.content' );
		$this->cContent->ensureIndex ( array (
				'url' => 1 
		), array (
				"unique" => true 
		) );
		
		$this->cContent->ensureIndex ( array (
				'keyword' => 1 
		) );
		
		if (! $this->cContent->count ()) {
			/**
			 * 添加热门职位
			 */
			$this->insert ( '司机' );
			$this->insert ( '兼职' );
			$this->insert ( '会计' );
			$this->insert ( '销售' );
			$this->insert ( '工程师' );
		}
		
		$this->curl = new \Tool\Curl ();
		$this->curl->setReferer ( 'http://www.sogou.com/zhaopin' );
	}
	public function insert($keyword, $page = 1) {
		if ($this->cContent->findOne ( array (
				'keyword' => $keyword,
				'page' => $page 
		) )) {
			return true;
		}
		
		$data = array (
				'keyword' => $keyword,
				'page' => $page,
				'url' => 'http://job.vr.sogou.com/job?&ie=utf8&f_startdate=3&page=' . $page . '&query=' . urlencode ( $keyword ) . '&rnd=1365758745484000604',
				'isFetch' => false,
				'isParse' => false,
				'insertTime' => new MongoDate () 
		);
		
		try {			
			$this->cContent->insert ( $data );
		} catch ( \MongoException $e ) {
			throw $e;
		}
	}
	public function run() {
		$row = $this->cContent->findOne ( array (
				'isFetch' => false 
		) );
		if ($row) {
			echo "fetch url {$row ['url']} \n";
			$this->curl->setUrl ( $row ['url'] );
			$row ['content'] = $this->curl->fetch ();
			$row ['isFetch'] = true;
			
			try {
				$this->cContent->save ( $row );
			} catch (MongoCursorTimeoutException $e) {
				sleep(130);
			}
		}
	}
}
class parser {
	protected $taskFetch;
	protected $taskData;
	protected $cContent;
	protected $filterSplitWord;
	public function __construct() {
		$this->cContent = new \Sys\Mongo\Collection ( 'sogou.content' );
		$this->filterSplitWord = new \Filter\SplitWord ();
		$this->taskFetch = new fetch ();
		$this->taskData = new data ();
	}
	public function run() {
		
		try {
			
			$row = $this->cContent->findOne ( array (
				'isFetch' => true,
				'isParse' => false 
			) );
			
		} catch (MongoCursorTimeoutException $e) {
			sleep(140);
			return true;
		}
		
		
		if ($row) {
			$content = trim ( $row ['content'] );
			$content = substr ( $content, 10, - 2 );
			
			$data = json_decode ( $content, true );
			$list = $data ['result'] [0] ['srrs'];
			
			if ($list) {
				
				try {
					
					foreach ( $list as $item ) {
						$this->parseItem ( $item, $row );
					}
					
					$row ['isParse'] = true;
					$row ['parseTime'] = time ();
					$this->cContent->save ( $row );
					
					// 添加下一页任务,为数据范围宽度，限制采集页数
					$nextPage = $row ['page'] + 1;
					if ($nextPage < 6) {
						$this->taskFetch->insert ( $row ['keyword'], $nextPage );
					}
				} catch ( \MongoException $e ) {
					throw $e;
				}
			} else {
				$this->cContent->remove ( $row );
			}
		}
	}
	
	/**
	 * 分析处理单条职位数据
	 */
	public function parseItem($item, $row) {
		$item = array_map ( array (
				$this,
				'clearEm'
		), $item );

		$keywords = $item ['title'];
		$this->filterSplitWord->filter ( $keywords );

		foreach ( $keywords as $keyword ) {
			$this->taskFetch->insert ( $keyword );
		}
		
		$item ['keyword'] = $keywords;
		
		$this->taskData->insert ( array (
				's_id' => $row ['_id'],
				'data' => $item,
				'isUpdate' => false,
				'insertTime' => time () 
		) );
	}
	

	public function clearEm($string) {
		$string = str_replace ( array (
				'[em]',
				'[/em]'
		), array (
				'',
				''
		), $string );
		return $string;
	}	
}
class data {
	protected $collection;
	protected $mCity;
	protected $cJob;
	protected $wrongCityNameList;
	protected $rightCityNameList;
	public function __construct() {
		$this->collection = new \Sys\Mongo\Collection ( 'sogou.data' );
		$this->collection->ensureIndex ( array (
				'data.joburl' => 1
		), array (
				"unique" => true
		) );

		$this->cJob = new \Sys\Mongo\Collection ( 'job' );
		$this->collection->ensureIndex ( array (
				'keyword' => 1
		));
		$this->collection->ensureIndex ( array (
				'cityCode' => 1
		));
		$this->collection->ensureIndex ( array (
				'salaryMax' => 1
		));
		$this->collection->ensureIndex ( array (
				'salaryMin' => 1
		));
		$this->collection->ensureIndex ( array (
				'experienceMax' => 1
		));
		$this->collection->ensureIndex ( array (
				'experienceMin' => 1
		));
		
		$this->mCity = model ( 'City' );
		
		$old = $new = array ();
		$replace = '襄樊|襄阳
				甘南州|甘南藏族自治州
				南沙开发区|广州市
				湘西州|湘西土家族苗族自治州
				燕郊开发区|北京市
				黄南州|黄南藏族自治州
				迪庆州|迪庆藏族自治州
				海北州|海北藏族自治州
				甘孜州|甘孜藏族自治州
				甘南州|甘南藏族自治州
				黔西南州|黔西南布依族苗族自治州
				德宏州|德宏傣族景颇族自治州
				临夏州|临夏回族自治州
				甘南州|甘南藏族自治州
				楚雄州|楚雄彝族自治州
				大理州|大理白族自治州
				恩施州|恩施土家族苗族自治州
				怒江州|怒江傈僳族自治州
				红河州|红河哈尼族彝族自治州
				阿坝州|阿坝藏族羌族自治州
				黔南州|黔南布依族苗族自治州
				德宏州|德宏傣族景颇族自治州
				凉山州|凉山彝族自治州
				红河州|红河哈尼族彝族自治州
				临夏州|临夏回族自治州
				黄南州|黄南藏族自治州
				海西州|海西蒙古族藏族自治州
				玉树州|玉树藏族自治州
				甘南州|甘南藏族自治州
				西双版纳州|西双版纳傣族自治州
				黔东南州|黔东南苗族侗族自治州';
		
		foreach ( explode ( "\n", $replace ) as $item ) {
			list ( $itemOld, $itemNew ) = explode ( '|', $item );
			$old [] = trim ( $itemOld );
			$new [] = trim ( $itemNew );
		}
		
		$this->wrongCityNameList = $old;
		$this->rightCityNameList = $new;
	}
	public function insert($data) {
		if ($data ['data'] ['source'] == '百伯') {
			echo "skip '百伯'\n";
			return true;
		}

		echo "insert data : " . $data ['data'] ['title'] . "\n";

		try {
			$this->collection->insert ( $data );
		} catch (MongoCursorException $e) {
			if ($e->getCode() == '11000') {
				echo "skip repeat data\n";
			} else {
				throw $e;
			}
		}
	}
	public function run() {
		$cursor = $this->collection->find ( array (
				'isUpdate' => false 
		) );
		
		$cityMap = array ();
		while ( $cursor->hasNext () ) {
			try {
				$row = $cursor->getNext ();
			} catch (MongoCursorTimeoutException $e) {
				sleep(120);
				continue;
			}
			$data = $row ['data'];

			$cityName = $data ['city'];
			if (isset ( $cityMap [$cityName] )) {
				$cityInfo = $cityMap [$cityName];
			} else {
				$rightCityName = str_replace ( $this->wrongCityNameList, $this->rightCityNameList, $cityName );
				$cityRow = $this->mCity->findByName ( $rightCityName );
				if (! $cityRow) {
					echo 'can not find city name : ' . $cityName . "\n";
					// continue;
					exit ();
				}
				$cityMap [$cityName] = $cityInfo = array (
						'code' => $cityRow ['code'],
						'name' => $cityRow ['name'] 
				);
			}
			$data ['cityCode'] = $cityInfo ['code'];
			$data ['cityName'] = $cityInfo ['name'];
			
			
			
			list($data ['salaryMin'], $data ['salaryMax']) = $this->filterSalary($data ['salary']);
			list($data ['experienceMin'], $data ['experienceMax']) = $this->filterExperience($data ['experience']);
			$data ['type'] = $this->filterType($data ['type']);

			
			
			$this->cJob->insert ( $data );
			echo "insert {$data ['cityName']}:{$data ['cityCode']} {$data['title']}\n";
			
			$row ['isUpdate'] = true;
			$row ['dataId'] = $data ['_id'];
			$this->collection->save ( $row );
		}
	}

	protected function filterType ($type) {
		list ( $type ) = explode ( ',', $type );
		$type = strip_tags($type);
		return $type;
	}

	protected function filterExperience($experience) {
		$cNum = array (
				'一',
				'二',
				'三',
				'四',
				'五',
				'六',
				'七',
				'八',
				'九',
				'十'
		);
		$eNum = array (
				1,
				2,
				3,
				4,
				5,
				6,
				7,
				8,
				9,
				'*10+'
		);

		$experience = str_replace ( $cNum, $eNum, $experience );
		$experience = preg_replace ( '/[^0-9\*\+]+/', ' ', $experience );
		$experience = trim ( $experience );
		$experience = preg_replace ( '/\s{2,}+/', ' ', $experience );
			
		if (strpos ( $experience, ' ' )) {
			list ( $min, $max ) = explode ( ' ', $experience );
		} else {
			$min = $max = $experience;
		}
			
		if (substr ( $min, 0, 3 ) == '*10') {
			$min = '1' . $min;
		}
		if (substr ( $min, - 4 ) == '*10+') {
			$min .= '0';
		}
		$min = (int) eval ( 'return ' . $min . ';' );
			
		if (substr ( $max, 0, 3 ) == '*10') {
			$max = '1' . $max;
		}
		if (substr ( $max, - 4 ) == '*10+') {
			$max .= '0';
		}
		$max = (int) eval ( 'return ' . $max . ';' );
		return array($min, $max);
	}
	
	protected function filterSalary ($salary) {
		list ( $salary ) = explode ( ',', $salary );
		$salaryFilter = $salary;
		$salaryFilter = preg_replace ( '/[^0-9]+/', ' ', $salaryFilter );
		$salaryFilter = trim ( $salaryFilter );
		$salaryFilter = preg_replace ( '/\s{2,}+/', ' ', $salaryFilter );
			
		if (strpos ( $salaryFilter, ' ' )) {
			list ( $min, $max ) = explode ( ' ', $salaryFilter );
		} else {
			$min = $max = $salary;
		}
			
		$min = ( int ) $min;
		$max = ( int ) $max;
			
		if ($min || $max) {
			if (strpos ( $salary, '万' ) || strpos ( $salary, '萬' )) {
				$min = $min * 10000;
				$max = $max * 10000;
			}
		
			if (strpos ( $salary, '天' ) || strpos ( $salary, '日' )) {
				$min *= 30;
				$max *= 30;
			} else if (strpos ( $salary, '星期' ) || strpos ( $salary, '周' )) {
				$min *= 4;
				$max *= 4;
			} else if (strpos ( $salary, '年' )) {
				$min = $min / 12;
				$max = $max / 12;
			}

			$min = intval ( $min );
			$max = intval ( $max );
		}

		return array($min, $max);
	}
}