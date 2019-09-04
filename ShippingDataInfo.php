<!DOCTYPE html>
<html>
<head>
<title>Page Title</title>
</head>
<body>
<?php

//Assuming CacheInterface.php is in the same folder 
require "CacheInterface.php";
Class User implements CacheInterface{
	//inputvalue = request body 
	private $inputvalue;
	
	// url = API Request
	private $url;
	
	//api_key = username:password
	private $api_key;
 
	//constructor to initialize data
	function __construct( $inputvalue, $url,$api_key) {
		$this->inputvalue = $inputvalue;
		$this->url = $url;
		$this->api_key = $api_key;
	}
 
	//output the response of API request and store it in cache for 5 min or retrieve the result if already present in cache
	function getResult() {
		
		//Check if the result is already cached, if not proceed inside if loop otherwise else
		if (!$data = $this->get($this->api_key)) {
			require "vendor/autoload.php";
			$client = new GuzzleHttp\Client();
			
			//initialize headers to accept json request body, add authorization and encode api_key
			$headers = [
			'Content-type' => 'application/json; charset=utf-8',
			'Accept' => 'application/json',
			'Authorization' => 'Basic ' . base64_encode($this->api_key),
			];

			//send API request and store the result in response variable
			$response = $client->request('POST', $this->url,['headers' => $headers, 'body' => $this->inputvalue]);
			
			//Check the status code before accessing response body
			if($response->getStatusCode()==200){
				
				//Store the response in the cache for 5 minutes
				$this->set($this->api_key,$response->getBody(),300);
				
				echo "Uncached Data   ";
				
				//return response body
				return $response->getBody();
			}
	}	else{
				echo "Cached Data   ";
				
				//return cached data
				return json_encode($this->get($this->api_key));
			}	
	}
	
	//Store the response to cache file
	public function set(String $key,$value,int $duration){
		//create a cache file to store response
		$h = fopen('s_cache' .  md5($key),'w');
		
		//Throw exception if file cannot be created
		if (!$h) throw new Exception('Could not write to cache');
		
		//store data in an array with first value as time and second as response body
		$data = array(time()+$duration,json_decode($value));	

		//store structured data to cache
		$data = json_encode($data);
		
		//Throw exception if for some reason response cannot be written to a file
		if (fwrite($h,$data)===false) {
		  throw new Exception('Could not write to cache');
		}
		
		//Close the file after the job is done
		fclose($h);
	}
	
	//Retrieve response from cache file
	public function get(String $key){
		$filename = 's_cache' .  md5($key); 

		//check for the cache file existence
		if (!file_exists($filename) || !is_readable($filename)) {
			return false;
		}
		
		$data = file_get_contents($filename);
		$data = json_decode($data,true);
		
		if (!$data) {
		 // Unlink the file when unserializing failed
			unlink($filename);
		 
			return false;

		}
		// checking if the data was expired
		if (time() > $data[0]) {

			// Unlink file 
			unlink($filename);
		 
			return false;
		}
		
		//return stored response in cache file
		return $data[1];
    
	}
 
 }
 
 
//json request body as per given address
$input = '{"recipient": { "address1": "11025 Westlake Dr","city": "Charlotte","country_code": "US","state_code": "NC","zip": 28273},"items": [{"quantity": 2,"variant_id": 7679}]}';

//create an object of user and get the response for the request
$output = new User( $input,'https://api.printful.com/shipping/rates','77qn9aax-qrrm-idki:lnh0-fm2nhmp0yca7' );
$response=$output->getResult();

//output the response
echo $response;
?>
</body>
</html>
