<?php

namespace Project\FancyBlocks\TwitterFeed;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestEdit;

class TwitterFeedBlock extends BlockController
{
    
    /*
     * @var string
     */    
    protected $cacheId = 'twitter_feed_cache';
    
    /*
     * Cache lifetime, 15 minutes by default
     * @var integer
     */
    protected $cacheLifeTime = 900;
    
    /*
     * @var string
     */
    protected $consumerKey = null;
    
    /*
     * @var string
     */
    protected $consumerSecret = null;
    
    /*
     * @var string
     */
    protected $accessToken = null;
    
    /*
     * @var string
     */
    protected $feedUrl = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    
    /*
     * @var array
     */
    protected $feedParameters = array(
        'trim_user' => true,
        'include_rts' => false,
        'exclude_replies' => true,
    );
    
    /*
     * @var string
     */
    protected $tokenUrl = 'https://api.twitter.com/oauth2/token';
    
        
	public function doExecute()
    {
        $tweets = array();
        $response = $this->getResponse();
        $request = $this->getRequest();
        
        if ($this->loadConfiguration()) {
            
            $cacheAdapter = ObjectRepository::getCacheAdapter($this);
            if ($cacheAdapter->contains($this->cacheId)) {
                $tweets = $cacheAdapter->fetch($this->cacheId);
            } else {
                $this->getAccessToken();
                $tweets = $this->getTwitterFeed();
                $cacheAdapter->delete($this->cacheId);
                $cacheAdapter->save($this->cacheId, $tweets, $this->cacheLifeTime);
            }
            
            $tweets = $this->formatTweets($tweets);
            
            $response->assign('tweets', $tweets)
                    ->outputTemplate('index.html.twig');
        }
        else {
            if ($request instanceof PageRequestEdit) {
                $response->outputTemplate('configuration-missing.html.twig');                
            }
        }
    }
    
    
    protected function loadConfiguration()
    {
        $result = true;
        
        $ini = ObjectRepository::getIniConfigurationLoader($this);
        $this->consumerKey = $ini->getValue('twitter', 'consumer_key');
        $this->consumerSecret = $ini->getValue('twitter', 'consumer_secret');
        
        if (!$this->consumerKey || !$this->consumerSecret) {
            throw new Exception('Could not find Twitter application keys in supra.ini');
        }
        
        $twitterAccount = $this->getPropertyValue('account');
        if (!$twitterAccount) {
            $result = false;
        } else {
            $this->feedParameters['screen_name'] = $twitterAccount;
        }
        
        return $result;
    }
    
    
    protected function buildUrl()
    {
        $url = $this->feedUrl;
        
        $limit = $this->getPropertyValue('limit');
        if ($limit) {
            $this->feedParameters['count'] = $limit;
        }
        
        $query = http_build_query($this->feedParameters);
        $url .= '?' . $query;
        
        return $url;
    }
    
    
    protected function getAccessToken()
    {
        $encoded_consumer_key = urlencode($this->consumerKey);
        $encoded_consumer_secret = urlencode($this->consumerSecret);
        $bearer_token = base64_encode($encoded_consumer_key.':'.$encoded_consumer_secret);

        $url = $this->tokenUrl;
        $headers = array( 
            "POST /oauth2/token HTTP/1.1", 
            "Host: api.twitter.com", 
            "User-Agent: my Twitter App v.1",
            "Authorization: Basic ".$bearer_token."",
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
            "Content-Length: 29",
        ); 

        $curl = curl_init();
        $options = array(
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => 1,
        );
        
        curl_setopt_array($curl, $options);
        $json = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($status == 200) {
            $result = json_decode($json);
            $this->accessToken = $result->access_token;
        } else {
            throw new Exception('Could not get access token for Twitter API');
        }
    }
    
    
    protected function getTwitterFeed()
    {
        $result = array();
        $header = array('Authorization: Bearer '.$this->accessToken);
        
        $url = $this->buildUrl();
        
        $options = array(
            CURLOPT_SSLVERSION => 3,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_RETURNTRANSFER => true,
        );
        
        $feed = curl_init();
        curl_setopt_array($feed, $options);
        
        $json = curl_exec($feed);
        $status = curl_getinfo($feed, CURLINFO_HTTP_CODE);
        
        curl_close($feed);
        
        if ($status == 200) {
            $result = json_decode($json);
        }

        return $result;
    }
    
    
    protected function formatTweets($tweets)
    {
        foreach($tweets as &$tweet) {
            $tweet->created_at = $this->formatTweetDate($tweet->created_at);
            $tweet->text = $this->formatTweetText($tweet->text);
        }
        
        return $tweets;
    }
    
    
    private function formatTweetDate($date)
    {
        $tweetTimeStamp = strtotime($date);
        $now = time();
        
        $seconds = $now - $tweetTimeStamp;

        if ($seconds <= 1) {
            return 'just now';
        } else if ($seconds < 20) {
            return $seconds . ' seconds ago';
        } else if ($seconds < 40) {
            return 'half a minute ago';
        } else if ($seconds < 60) {
            return 'less than a minute ago';
        } else if ($seconds <= 90) {
            return 'one minute ago';
        } else if ($seconds <= 3540) {
            return round($seconds / 60) . ' minutes ago';
        } else if ($seconds <= 5400) {
            return '1 hour ago';
        } else if ($seconds <= 86400) {
            return round($seconds / 3600) . ' hours ago';
        } else if ($seconds <= 129600) {
            return '1 day ago';
        } else if ($seconds < 604800) {
            return round($seconds / 86400) . ' days ago';
        } else if ($seconds <= 777600) {
            return '1 week ago';
        }
        
        //Format %d %m %y
        $now = new \DateTime('now');
        $tweetDate = new \DateTime();
        $tweetDate->setTimestamp($tweetTimeStamp);
        
        $diff = $now->diff($tweetDate);
        if ($diff->y > 1) {
            return $tweetDate->format('d F Y');
        } else {
            return $tweetDate->format('d F');
        }
    }
    
    
    private function formatTweetText($text)
    {
        $temp = $text;
        
        //http link
        $temp = preg_replace_callback('/(\b(https?|ftp|file):\/\/[\-A-Z0-9+&@#\/%?=~_|!:,.;]*[\-A-Z0-9+&@#\/%=~_|])/i', function($matches) { 
            $result = '<a href="' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
            return $result;
        }, $temp);
        
        //@username
        $temp = preg_replace_callback('/(^|\s)@(\w+)/', function($matches) {
            $username = trim(str_replace("@", "", $matches[0]));
            $result = '<a href="http://twitter.com/' . $username . '" target="_blank">' . $matches[0] . '</a>';
            return $result;
        }, $temp);
        
        // #hashtag
        $temp = preg_replace_callback('/(^|\s)#(\w+)/', function($matches) {
            $hashtag = trim(str_replace("#","%23", $matches[0]));
            return '<a href="http://search.twitter.com/search/' . $hashtag . '" target="_blank">' . $matches[0] . '</a>';
        }, $temp);
    
        return $temp;
    }
}