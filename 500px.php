<?php

require_once 'lib/init.php';
//require_once LIB . '/TwitterOAuth/TwitterOAuth.php';
use Abraham\TwitterOAuth\TwitterOAuth;

switch ($_REQUEST['do']) {
	/*case 'testing':
		$access_token = $_SESSION['500px_access_token'];
		$connection = new TwitterOAuth(FIVEHUNDREDPX_API_KEY, FIVEHUNDREDPX_API_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
		$connection->setTimeouts(10, 60); // Large Flickr files could take a long time to 500px to download
		
		$body = json_encode(array('add' => array('photos' => array(148682531))));
		
		$gallery_result = $connection->put('users/82770/galleries/22851449/items', array(), $body);
		
		echo '<pre>';
		var_dump($gallery_result);
	break;*/
	
	case 'create_gallery':
		$access_token = $_SESSION['500px_access_token'];
		$connection = new TwitterOAuth(FIVEHUNDREDPX_API_KEY, FIVEHUNDREDPX_API_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
		$connection->setTimeouts(10, 60); // Large Flickr files could take a long time to 500px to download
		
		$me = $connection->get('users');
		$user_id = $me->user->id;
		
		$gallery = $connection->post("users/" . $user_id . '/galleries', array(
			'name' => 'Migrated from Flickr',
			'description' => "Photos migrated from Flickr using Kaitlyn's Migration tool",
			'privacy' => 1,
			'kind' => 4,
			'custom_path' => 'migrated' . rand(0, 1000)
		));
		
		echo json_encode($gallery);
		die();
	break;
	
	case 'upload':
		if ($_SESSION['500px_access_token']) {
			$access_token = $_SESSION['500px_access_token'];
			$connection = new TwitterOAuth(FIVEHUNDREDPX_API_KEY, FIVEHUNDREDPX_API_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
			$connection->setTimeouts(5, 60); // Large Flickr files could take a long time to 500px to download
			
			$photos = $connection->get("photos", array('feature' => 'popular'));
			
			$key_response = $connection->post('photos', array(
				'name' => $_REQUEST['title'],
				'description' => $_REQUEST['description'],
				'category' => 0,
				'tags' => str_replace(' ', ',', $_REQUEST['tags']),
				'privacy' => 1
			));

			$upload_key = $key_response->upload_key;
			$photo_id = $key_response->photo->id;
			$access_key = $_SESSION['500px_access_token']['oauth_token'];
			
			$upload_result = $connection->post('upload', array(
				'photo_id' => $photo_id,
				'consumer_key' => FIVEHUNDREDPX_API_KEY,
				'upload_key' => $upload_key,
				'access_key' => $access_key,
				'remote_url' => $_REQUEST['url_o'],
			));

			if ($upload_result->error == 'None.') {
				$body = json_encode(array('add' => array('photos' => array($photo_id))));
				$gallery_result = $connection->put('users/' . $_REQUEST['user_id'] . '/galleries/' . $_REQUEST['gallery_id'] . '/items', array(), $body);
				
				
				echo 'ok';
			} else {
				echo $result->error . ' - ' . $result->status;
			}
		} else {
			echo 'No access token';
		}
	break;
	
	default:
		if (!empty($_REQUEST['oauth_token'])) {
			$request_token = [];
			$request_token['oauth_token'] = $_SESSION['500px_oauth_token'];
			$request_token['oauth_token_secret'] = $_SESSION['500px_oauth_token_secret'];
			
			unset($_SESSION['500px_oauth_token']);
			unset($_SESSION['500px_oauth_token_secret']);

			if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
			    // Abort! Something is wrong.
			    echo 'Invalid validation!';
			    die();
			}
			
			$connection = new TwitterOAuth(FIVEHUNDREDPX_API_KEY, FIVEHUNDREDPX_API_SECRET, $request_token['oauth_token'], $request_token['oauth_token_secret']);
			$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
			$_SESSION['500px_access_token'] = $access_token;
			
			header('Location: index.php');
			
			die();
		}
		
		if ($_REQUEST['do'] == 'authorize' || empty($_SESSION['500px_oauth_token'])) {
			$return = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/500px.php';
		
			$connection = new TwitterOAuth(FIVEHUNDREDPX_API_KEY, FIVEHUNDREDPX_API_SECRET);
			$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $return));
			$_SESSION['500px_oauth_token'] = $request_token['oauth_token'];
			$_SESSION['500px_oauth_token_secret'] = $request_token['oauth_token_secret'];
			
			$url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
			
			header('Location: ' . $url);
		}
	break;
}

?>
