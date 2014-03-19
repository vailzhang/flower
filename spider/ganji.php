<?php
require '../../src/Sys/Functions/Common.php';
require '../../src/Tool/simple_html_dom.php';

app ( 'Cli' )->run ();

$taskType = $_SERVER ["argv"] [1];

$task = new $taskType ();

$stopFile = __DIR__ . '/ganjiStop.stop';
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
		$this->cContent = new \Sys\Mongo\Collection ( 'ganji.content' );
		$this->cContent->ensureIndex ( array (
				'url' => 1
		), array (
				"unique" => true
		) );

		$this->cContent->ensureIndex ( array (
				'keyword' => 1
		) );
		$this->curl = new \Tool\Curl ();
		$this->curl->setReferer ( 'http://www.ganji.com' );

		if (! $this->cContent->count ()) {
			/**
			 * 添加采集的初始数据
			 */

			$SourceUrls = array(
					'大学生兼职' => 'jzxuesheng',
					'实习' => 'jzshixisheng'
			);
			foreach ($SourceUrls as $type => $keyword){
				$this->insert($keyword);
			}
		}
	}
	//插入待解析的数据
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
				'url' =>'http://sh.ganji.com/'.$keyword.'/o'.$page.'/u1/',
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
 	//采集数据内页入库
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
//解析数据
class parser {
	protected $taskFetch;
	protected $taskData;
	protected $cContent;
	protected $filterSplitWord;
	public function __construct() {
		$this->cContent = new \Sys\Mongo\Collection ( 'ganji.content' );
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
			echo 'mongo exception';
			return true;
		}

		if ($row) {
			$content = trim ( $row ['content'] );
			if ($content) {
				try {
					//得到内页url保存入库
					$html = str_get_html($content);
					$urls = $html->find('a.list_title');
					if(empty($urls)){		//无法找到数据
						echo "Can\'t search innerUrl ! \n";
						$this->cContent->remove ( array('_id'=>$row['_id']),array("justOne" => true) );
						return true;
					}
					foreach ( $urls as $element){
						$innerUrl = 'http://sh.ganji.com'.$element ->href;
						$keywords = $title= $element ->plaintext;
						if($keywords){
							//title分词后再插入采集列表
							$this->filterSplitWord->filter ( $keywords );
							foreach ( $keywords as $keyword ) {
								$this->taskFetch->insert ( $keyword );
							}
						}
						if($innerUrl){
							$row['title'] = $title;
							$row['keywords'] = $keywords;
							$row['isInnerFetch'] = false;
							$row['innerUrl'] = $innerUrl;
							$row ['isParse'] = true;			//已经解析过列表页的
							$row ['parseTime'] = time ();
							echo 'parse : '.$innerUrl."\n";
							$this->cContent->save ( $row );
						}else{
							echo 'Inner url not found !';
						}
					}

					//添加下一页任务,为数据范围宽度，限制采集页数
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
		}else{
			echo 'Nothing to parser !';
			return true;
		}
	}
}
/**
 * 采集内页数据
 */
class innerFetch {
	protected $cContent;
	public function __construct() {
		$this->cContent = new \Sys\Mongo\Collection ( '51job.content' );
	}
	public function run() {
		try {
			$row = $this->cContent->findOne ( array (
					'isFetch' => true,
					'isParse' => true,
					'isInnerFetch' =>false,
			) );

		} catch (MongoCursorTimeoutException $e) {
			sleep(140);
			return true;
		}

		//处理内页url 获取到content数据入库
		if(!empty($row)){
			$url = $row['innerUrl'];
			echo 'fetch inner url :'.$url."\n";
			$this->curl = new \Tool\Curl ();
			$this->curl->setReferer ( 'http://www.51job.com' );
			$this->curl->setUrl($url);
			$content = $this->curl->fetch ();
			$row['content'] = $content;
			$row['isInnerFetch'] = true;
			$row['isInnerParse'] = false;
			$this->cContent->save($row);
		}else{
			echo "No inner url for fetch \n";
			return true;
		}
	}
}
/**
 * 解析内页数据
 */
class innerParser{
	protected $cContent;
	protected $taskData;
	public function __construct(){
		$this->cContent = new \Sys\Mongo\Collection ( '51job.content' );
		$this->taskData = new data ();
	}
	public function run(){
		try {
			$row = $this->cContent->findOne(array(
						'isFetch' => true,
						'isParse' => true,
						'isInnerFetch' =>true,
						'isInnerParse' => false
			));
		}catch (MongoCursorTimeoutException $e){
			sleep(140);
			return true;
		}

	if ($row) {
		$content = $row['content'];
		$html= str_get_html($content);
		$item = array();
		$item['title'] = $html->find('td.sr_bt',0)->plaintext;	//获取title

		$patternPre = mb_strpos($html, '<a target="_blank" style="font-size:14px;font-weight:bold;color:#000000;"') ;
		$patternNext = mb_strpos($html,  '<a href="#gsjj" class="orange"><strong>查看公司简介') ;
		$employer = mb_substr($html, $patternPre , $patternNext - $patternPre );//获取employer
		$item['employer'] = trim(strip_tags($employer),'&nbsp;');
		$item['employer'] = str_replace(array(" ","　","\t","\n","\r"),array("","","","",""),$item['employer']);

		$tem_arr = array();
		$dom = $html->find('table.jobs_1 td');
		foreach ($dom as $key=> $elem){
			$tem_arr[] =  $elem->plaintext;
			if($key == 4){
				break;
			}
		}
		$need_str = str_replace(array(" ","  ","\t","\n","\r","公司行业","公司性质","公司规模"), array("","","","","","","",""), $tem_arr[4]);
		$need_arr = explode("：&nbsp;&nbsp;", $need_str);
		array_shift($need_arr);
		$item['industry'] = $need_arr[0];
		$item['cType'] = $need_arr[1];
		$item ['cScale'] = $need_arr[2];

		$dom = $html->find('div.jobs_com div.grayline table.jobs_1 td.txt_2');
		$arr = array();
		foreach ($dom as $elem){
			$arr[] =  $elem->plaintext;
		}
		$item['modifyDate'] = $arr[0];
		$item['workPlace'] = $arr[1];
		$item['needPerson'] = (int)$arr[2];
		$first = mb_strpos($html, '<td class="txt_1" width="12%">工作年限：</td>');
		$second = mb_strpos($html,'<td class="txt_1" width="12%">语言要求：</td>');
		$third = mb_strpos($html,'<td class="txt_1" width="12%">学&nbsp;&nbsp;&nbsp;&nbsp;历：</td>');
		$fourth = mb_strpos($html,'<td class="txt_1" width="12%">薪水范围：</td>');

		$experience = mb_substr($html,$first ,150);
		$experience = strip_tags(mb_substr($experience, 0,mb_strpos($experience,'</td>',50)));

		$language = mb_substr($html,$second ,150);
		$language = strip_tags(mb_substr($language, 0,mb_strpos($language,'</td>',50)));

		$education = mb_substr($html,$third ,150);
		$education = strip_tags(mb_substr($education, 0,mb_strpos($education,'</td>',80)));

		$salary = mb_substr($html,$fourth ,150);
		$salary = strip_tags(mb_substr($salary, 0,mb_strpos($salary,'</td>',50)));

		$item['experience'] = empty($experience)?null:str_replace(array(" ","　","\t","\n","\r","工作年限："),array("","","","","",""),$experience);
		$item['language'] = empty($language)?null:str_replace(array(" ","　","\t","\n","\r","语言要求："),array("","","","","",""),$language);
		$item['education'] = empty($education)?null:str_replace(array(" ","　","\t","\n","\r","学&nbsp;&nbsp;&nbsp;&nbsp;历："),array("","","","","",""),$education);
		$item['salary'] = empty($salary)?null:str_replace(array(" ","　","\t","\n","\r","薪水范围："),array("","","","","",""),$salary);

		$first = mb_strpos($html, '职位描述：<br>');
		$second = mb_strpos($html, '<td colspan="6" align="center" class="txt_4 pot2">');
		$item['describe'] = str_replace(array(" ","　","\t","\n","\r","职位描述："),array("","","","","",""), strip_tags(mb_substr($html, $first,$second - $first)));

		$first = mb_strpos($html, '<strong>职位职能:</strong>');
		$second = mb_strpos($html, '<td colspan="6" style="width:100%" class="txt_4 wordBreakNormal job_detail"> ');
		$item['jobDetail'] = trim(str_replace(array(" ","　","\t","\n","\r","职位职能:"),array("","","","","",""), strip_tags(mb_substr($html, $first,$second - $first))),'&nbsp;');

		$item['cIntro'] = trim($html->find('p.txt_font',0)->plaintext,'&nbsp;');
		$item['source'] = '51job';
		$item['sourcelink'] = $row['innerUrl'];
		$item['joburl'] = $row['innerUrl'];
		$item['type'] = $row['fetchType'];
		$item['city'] = mb_strpos($item['workPlace'], '-') != false ? mb_substr($item['workPlace'], 0,mb_strpos($item['workPlace'], '-')) :$item['workPlace'];

		$item ['keyword'] = $row['keywords'];

		$this->taskData->insert ( array (		//把数据插入到data集合，  还未处理过
				's_id' => $row ['_id'],
				'innerUrl' => $row['innerUrl'],
				'data' => $item,
				'isUpdate' => false,
				'insertTime' => time ()
		) );
		$row['isInnerParse'] = true;
		$this->cContent->save($row);
		}else {
			echo "no inner data to parser ! \n";
			return true;
		}
	}
}

//处理数据
class data {
	protected $collection;
	protected $mCity;
	protected $cJob;
	protected $wrongCityNameList;
	protected $rightCityNameList;
	public function __construct() {
		$this->collection = new \Sys\Mongo\Collection ( '51job.data' );
		$this->collection->ensureIndex ( array (
				'data.joburl' => 1
		), array (
				"unique" => true
		) );

		$this->cJob = new \Sys\Mongo\Collection ( '51job' );
		$this->cJob->ensureIndex(array(
				'joburl' =>1
		),array(
				'unique' => true
		));
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

		echo "insert data : " . $data ['data'] ['title'] . "\n";

		//var_dump($data);exit;

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



			try {$this->cJob->insert ( $data );}catch (MongoCursorException $e) {
				if ($e->getCode() == '11000') {
					echo "skip repeat data\n";
				} else {
					throw $e;
				}
			}
			echo "insert {$data ['cityName']}:{$data ['cityCode']} {$data['title']}\n";

			$row ['isUpdate'] = true;
			$row ['dataId'] = $data ['_id'];
		}
			$this->collection->save ( $row );
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
		$experience = preg_replace('/[^0-9]+/', '', $experience);
		$min = (int)$experience;
		$max = null;
		/*$experience = preg_replace ( '/[^0-9\*\+]+/', ' ', $experience );
		$experience = trim ( $experience );
		$experience = preg_replace ( '/\s{2,}+/', ' ', $experience );

		if (strpos ( $experience, ' ' )) {
			list ( $min, $max ) = explode ( ' ', $experience );
		} else {
			$min = $max = $experience;
		}

		if (mb_substr ( $min, 0, 3 ) == '*10') {
			$min = '1' . $min;
		}
		if (mb_substr ( $min, - 4 ) == '*10+') {
			$min .= '0';
		}
		$min = (int) eval ( 'return ' . $min . ';' );

		if (mb_substr ( $max, 0, 3 ) == '*10') {
			$max = '1' . $max;
		}
		if (mb_substr ( $max, - 4 ) == '*10+') {
			$max .= '0';
		}
		$max = (int) eval ( 'return ' . $max . ';' );*/
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