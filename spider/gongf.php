<?php
require '../../src/Sys/Functions/Common.php';
require '../../src/Tool/simple_html_dom.php';

app ( 'Cli' )->run ();

$taskType = $_SERVER ["argv"] [1];

$task = new $taskType ();

$stopFile = __DIR__ . '/gongf.stop';
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
/*
 * array('_id','innerUrl','title')
 * 
 */
class fetch{
	protected $cUrl;
	protected $cFetchUrl;
	public function __construct(){
		$this->cFetchUrl = new Sys\Mongo\Collection('fetchUrl');
		$this->cFetchUrl->ensureIndex(array(
				'url' => 1
		),array(
				'unique' => true
		));
		$this->cFetchUrl->ensureIndex(array(
				'tag' => 1
		));
		$this->cUrl = new \Tool\Curl();
		$this->cUrl->setReferer('www.sohu.com');
	}
	public function run(){
		$this->cUrl->setUrl ('http://kungfu.sports.sohu.com');
		$html = $this->cUrl->fetch ();
		$str = str_get_html($html);
		$urls = $str->find('div.top a');
		$urlNexts = $str->find('div.cc li a');
		$urlLasts = $str->find('div.cut4 li a');
		$this->getInnerUrl($urls, 'hot');
		$this->getInnerUrl($urlNexts, 'warm');
		$this->getInnerUrl($urlNexts, 'normal');
		exit('over');
	}
	public function getInnerUrl(Array $urls, $tag){
		$urls = array_unique($urls);
		foreach ( $urls as $element){
			$innerUrl = $element ->href;
			$title= $element ->plaintext;
			$insertData = array(
					'url' => $innerUrl,
					'title' => $title,
					'tag' => $tag,
					'isFetch'=>false
			);
			echo "fetch url ".$innerUrl."\n";
			try {
				$isInserted = $this->cFetchUrl->findOne(array('url'=>$innerUrl));
				if ($isInserted) {
					continue;
				}
				$this->cFetchUrl->insert($insertData);
			}catch (MongoException $e){
				throw $e;
				//continue;
			}
		}
	}
	
}
/*
 * array(
 * 		'_id','title','content','insertTime','source','author','picUrl','tag'
 * )
 */
class parser{
	protected $cFetchUrl;
	protected $cNews;
	public function __construct(){
		$this->cFetchUrl = new Sys\Mongo\Collection('fetchUrl');
		$this->cNews = new Sys\Mongo\Collection('news');
		$this->cNews->ensureIndex(array('tag'=>1));
		$this->cUrl = new \Tool\Curl();
		$this->cUrl->setReferer('www.sohu.com');
	}
	public function run(){
		try {
			$fetchUrl = $this->cFetchUrl->findOne(array('isFetch'=>false));
		}catch (MongoCursorTimeoutException $e){
			sleep(1);
		}
		if (isset($fetchUrl['url'])) {
			$url = $fetchUrl['url'];
			$this->cUrl->setUrl($url);
			try {
				$html = $this->cUrl->fetch ();
			}catch (\Exception $e){
				$html = null;
				$this->cFetchUrl->remove(array('url'=>$url));
			}
			$str = str_get_html($html);
			$innerTitle = $str->find('h1',0)->plaintext;
			$content = $str->find('div[id=contentText]',0)->innertext;
			$time = $str->find('div.time',0)->plaintext;
			$source = str_replace(array(" ","　","\t","\n","\r","/","&nbsp;"),array("","","","","","",""), $str->find('div.source',0)->plaintext);
			$decribe = mb_substr(str_replace(array(" ","　","\t","\n","\r","/","&nbsp;"),array("","","","","","",""), strip_tags($content)), 0, 20);

			$isInsert = true;
			if (empty($innerTitle) || empty($content) || empty($time) || empty($source)) {
				$isInsert = false;
			}
			$str->clear();
			if ($isInsert) {
				try {
					echo 'parser url '.$url."\n";
					$this->cNews->insert(array(
							'url'=>$url,
							'title'=> trim($fetchUrl['title']),
							'innelTitle'=>trim($innerTitle),
							'content'=>trim($content),
							'tag' => $fetchUrl['tag'],
							'describe' => $decribe,
							'time'=>trim($time),
							'source'=>trim($source),
							'insertTime'=>new MongoDate()
					));
					$fetchUrl['isFetch'] = true;
					$this->cFetchUrl->save($fetchUrl);
				}catch (MongoException $e){
					throw $e;
				}
			}else{
				$this->cFetchUrl->remove(array('url'=>$url));
			}
		}else{
			exit('over');
		}
	}
}

