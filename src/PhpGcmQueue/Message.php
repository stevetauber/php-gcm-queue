<?php
namespace PhpGcmQueue;

/**
 * Class Message
 *
 * @package PhpGcmQueue
 * @author Steve Tauber <taubers@gmail.com>
 * @author Vladimir Savenkov <ivariable@gmail.com>
 */
class Message {

    /**
     * Max size for data.
     */
    const MAX_SIZE = 4096;

    /**
     * Max TTL.
     */
    const MAX_TTL = 2419200;

    /**
     * Min TTL.
     */
    const MIN_TTL = 0;

    /**
     * Max Registration IDs.
     */
    const MAX_REG_IDS = 1000;

    /**
     * Valid Priorities enum.
     */
    const VALID_PRIORITIES = [
        'high' => true,
        'normal' => true
    ];

    /**
     * This parameter specifies the recipient of a message.
     * The value must be a registration token, notification key, or topic.
     *
     * Required unless using Registration Ids.
     *
     * @var string
     */
    protected $to = '';

    /**
     * This parameter specifies a list of devices (registration tokens, or IDs) receiving a multicast message.
     * It must contain at least 1 and at most 1000 registration tokens.
     *
     * Use this parameter only for multicast messaging, not for single recipients. Multicast messages (sending
     * to more than 1 registration tokens) are allowed using HTTP JSON format only.
     *
     * Required unless using To.
     *
     * @var array
     */
    protected $registrationIds = [];

    /**
     * An arbitrary string (such as "Updates Available") that is used to collapse a group of like messages
     * when the device is offline, so that only the last message gets sent to the client.
     * This is intended to avoid sending too many messages to the phone when it comes back online.
     * Note that since there is no guarantee of the order in which messages get sent, the "last" message
     * may not actually be the last message sent by the application server.
     *
     * Optional.
     *
     * @var string|null
     */
    protected $collapseKey = null;

    /**
     * Sets the priority of the message. Valid values are "normal" and "high." On iOS, these correspond to
     * APNs priority 5 and 10.
     *
     * By default, messages are sent with normal priority. Normal priority optimizes the client app's battery
     * consumption, and should be used unless immediate delivery is required. For messages with normal priority,
     * the app may receive the message with unspecified delay.
     *
     * When a message is sent with high priority, it is sent immediately, and the app can wake a sleeping device
     * and open a network connection to your server.
     *
     * Valid values are high and normal.
     *
     * For existing iOS client apps that do not explicitly set delivery priority, GCM's default value starting
     * 8/13/2015 results in a change in app behavior. In such cases, you'll need to start explicitly setting
     * high priority for messages that require delivery without delay.
     *
     * Optional
     *
     * @var string|null
     */
    protected $priority = 'high';

    /**
     * On iOS, use this field to represent content-available in the APNS payload. When a notification or message
     * is sent and this is set to true, an inactive client app is awoken. On Android, data messages wake the app
     * by default. On Chrome, currently not supported.
     *
     * Optional
     *
     * @var bool
     */
    protected $contentAvailable = false;

    /**
     * When this parameter is set to true, it indicates that the message should not be sent until the device
     * becomes active.
     *
     * Optional.
     *
     * @var bool
     */
    protected $delayWhileIdle = false;

    /**
     * This parameter specifies how long (in seconds) the message should be kept in GCM storage if the device
     * is offline. The maximum time to live supported is 4 weeks, and the default value is 4 weeks.
     *
     * Optional
     *
     * @var int
     */
    protected $timeToLive = null;

    /**
     * This parameter specifies the package name of the application where the registration tokens must match
     * in order to receive the message.
     *
     * Optional.
     *
     * @var string|null
     */
    protected $restrictedPackageName = '';

    /**
     * This parameter, when set to true, allows developers to test a request without actually sending a message.
     *
     * Optional.
     *
     * @var bool
     */
    protected $dryRun = false;

    /**
     * This parameter specifies the custom key-value pairs of the message's payload.
     *      For example, with data:{"score":"3x1"}:
     *
     * On Android, this would result in an intent extra named score with the string value 3x1.
     *
     * On iOS, if the message is sent via APNS, it represents the custom data fields. If it is sent via GCM
     * connection server, it would be represented as key value dictionary in
     * AppDelegate application:didReceiveRemoteNotification:.
     *
     * The key should not be a reserved word ("from" or any word starting with "google" or "gcm"). Do not use
     * any of the words defined in this table (such as collapse_key).
     *
     * Values in string types are recommended. You have to convert values in objects or other non-string data
     * types (e.g., integers or bools) to string.
     *
     * Optional.
     *
     * @var array|null
     */
    protected $data = null;

    /**
     * This parameter specifies the predefined, user-visible key-value pairs of the notification payload.
     * @see https://developers.google.com/cloud-messaging/http-server-ref#notification-payload-support
     *
     * @var array
     */
    protected $notification = [];

    /**
     * Constructor.
     *
     * @param string|array $target
     *
     * @throws PhpGcmQueueException
     */
    public function __construct($target) {
        if(is_string($target)) {
            $this->to = $target;
        } elseif(is_array($target)) {
            $this->registrationIds = $target;
        } else {
            throw new PhpGcmQueueException('GCM\Client::__construct - Invalid or Missing Target', PhpGcmQueueException::INVALID_TARGET);
        }
    }

    /**
     * Create a Message object from array.
     *
     * @param array $array Array of params to set on the object.
     *
     * @return Message
     * @throws PhpGcmQueueException When required params not sent.
     */
    public static function fromArray(array $array) {
        $target = null;
        if(isset($array['to'])) {
            $target = $array['to'];
            unset($array['to']);
        } elseif (isset($array['registration_ids'])) {
            $target = $array['registration_ids'];
            unset($array['registration_ids']);
        }
        if($target) {
            $return = new Message($target);
            foreach ($array as $k => $v) {
                $methodName = 'set' . preg_replace_callback('/(?:^|_)(.?)/',
                        function($m) { return strtoupper($m[1]); }
                        , $k);
                if (method_exists($return, $methodName)) {
                    $return->$methodName($v);
                }
            }
            return $return;
        } else {
            throw new PhpGcmQueueException('GCM\Client::fromArray - Invalid or Missing Target: ' . print_r($array, true) , PhpGcmQueueException::INVALID_TARGET);
        }
    }

    /**
     * Convert to an array
     *
     * @return array
     */
    public function toArray() {
        $return = [];
        if($this->to !== '') {
            $return['to'] = $this->to;
        }
        if($this->registrationIds !== []) {
            $return['registration_ids'] = $this->registrationIds;
        }
        if($this->collapseKey !== null) {
            $return['collapse_key'] = $this->collapseKey;
        }
        if($this->priority !== 'high') {
            $return['priority'] = $this->priority;
        }
        if($this->contentAvailable !== false) {
            $return['content_available'] = $this->contentAvailable;
        }
        if($this->delayWhileIdle !== false) {
            $return['delay_while_idle'] = $this->delayWhileIdle;
        }
        if($this->timeToLive !== null) {
            $return['time_to_live'] = $this->timeToLive;
        }
        if($this->restrictedPackageName !== '') {
            $return['restricted_package_name'] = $this->restrictedPackageName;
        }
        if($this->dryRun !== false) {
            $return['dry_run'] = $this->dryRun;
        }
        if($this->data !== null) {
            $return['data'] = $this->data;
        }
        if($this->notification !== null) {
            $return['notification'] = $this->notification;
        }
        return $return;
    }

    /**
     * Convert to a JSON string.
     *
     * @return string
     */
    public function toJson() {
        return json_encode($this->toArray());
    }

    /**
     * To String.
     *
     * @return string
     */
    public function __toString() {
        return json_encode($this->toArray());
    }

    /**
     * Get To.
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set To.
     *
     * @param string $to
     *
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Get Registration IDs.
     *
     * @return array
     */
    public function getRegistrationIds() {
        return $this->registrationIds;
    }

    /**
     * Set Registration IDs.
     *
     * @param array $registrationIds
     *
     * @return $this
     * @throws PhpGcmQueueException When invalid number of Registration IDs.
     */
    public function setRegistrationIds(array $registrationIds) {
        $count = count($registrationIds);
        if (!$count || $count > self::MAX_REG_IDS) {
            throw new PhpGcmQueueException('GCM\Client->setRegistrationIds - Must contain 1-1000 (inclusive) Registration IDs. Count: ' . $count, PhpGcmQueueException::MALFORMED_REQUEST);
        }
        $this->registrationIds = $registrationIds;
        return $this;
    }

    /**
     * Get Collapse Key.
     *
     * @return null|string
     */
    public function getCollapseKey() {
        return $this->collapseKey;
    }

    /**
     * Set Collapse Key.
     *
     * @param string $collapseKey
     *
     * @return $this
     */
    public function setCollapseKey($collapseKey) {
        $this->collapseKey = $collapseKey;
        return $this;
    }

    /**
     * Get Priority.
     *
     * @return null|string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set Priority.
     *
     * @param string $priority
     *
     * @return $this
     * @throws PhpGcmQueueException
     */
    public function setPriority($priority)
    {
        if(!array_key_exists($priority, self::VALID_PRIORITIES)) {
            throw new PhpGcmQueueException('GCM\Client->setPriority - Must be high or normal', PhpGcmQueueException::INVALID_PRIORITY);
        }
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get Content Available.
     *
     * @return bool
     */
    public function getContentAvailable()
    {
        return $this->contentAvailable;
    }

    /**
     * Set Content Available.
     *
     * @param bool $contentAvailable
     *
     * @return $this
     */
    public function setContentAvailable($contentAvailable)
    {
        $this->contentAvailable = $contentAvailable;
        return $this;
    }

    /**
     * Get Delay While Idle.
     *
     * @return bool
     */
    public function getDelayWhileIdle() {
        return $this->delayWhileIdle;
    }

    /**
     * Set Delay While Idle.
     *
     * @param bool $delayWhileIdle
     *
     * @return $this
     */
    public function setDelayWhileIdle($delayWhileIdle) {
        $this->delayWhileIdle = $delayWhileIdle;
        return $this;
    }

    /**
     * Get Time To Live.
     *
     * @return int
     */
    public function getTimeToLive() {
        return $this->timeToLive;
    }

    /**
     * Set Time To Live.
     *
     * @param integer $timeToLive Time to Live.
     *
     * @return $this
     * @throws PhpGcmQueueException When TTL is not null|integer OR TTL is not within range
     */
    public function setTimeToLive($timeToLive) {
        if(!is_null($timeToLive) && !is_numeric($timeToLive)) {
            throw new PhpGcmQueueException('GCM\Client->setTimeToLive - Invalid TimeToLive: ' . $timeToLive, PhpGcmQueueException::INVALID_TTL);
        } else if(is_numeric($timeToLive) && ($timeToLive < self::MIN_TTL || $timeToLive > self::MAX_TTL)) {
            throw new PhpGcmQueueException('GCM\Client->setTimeToLive - TimeToLive must be between '
                . self::MIN_TTL . ' and ' . self::MAX_TTL . '. Value: ' . $timeToLive, PhpGcmQueueException::OUTSIDE_TTL);
        }
        $this->timeToLive = $timeToLive;
        return $this;
    }

    /**
     * Get Restricted Package Name.
     * 
     * @return null|string
     */
    public function getRestrictedPackageName() {
        return $this->restrictedPackageName;
    }

    /**
     * Set Restricted Package Name.
     * 
     * @param string $restrictedPackageName
     * @return $this
     */
    public function setRestrictedPackageName($restrictedPackageName) {
        $this->restrictedPackageName = $restrictedPackageName;
        return $this;
    }

    /**
     * Get Dry Run.
     *
     * @return bool
     */
    public function getDryRun() {
        return $this->dryRun;
    }

    /**
     * Set Dry Run.
     *
     * @param bool $dryRun
     *
     * @return $this
     */
    public function setDryRun($dryRun) {
        $this->dryRun = $dryRun;
        return $this;
    }
    
    /**
     * Get Data.
     *
     * @return array|null
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set Data.
     *
     * @param array $data Data to send.
     *
     * @return $this
     * @throws PhpGcmQueueException When encoded JSON exceeds MAX_SIZE bytes.
     */
    public function setData(array $data) {
        if (strlen(json_encode($data)) > Message::MAX_SIZE) {
            throw new PhpGcmQueueException('GCM\Client->setData - Data payload exceeds limit (max ' . Message::MAX_SIZE .' bytes)', PhpGcmQueueException::MALFORMED_REQUEST);
        }
        $this->data = $data;
        return $this;
    }

    /**
     * Get Notification.
     *
     * @return array
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * Set Notification.
     *
     * @param array $notification
     *
     * @return $this
     */
    public function setNotification($notification)
    {
        //TODO: Create Notification object for Android,iOS,watches
        $this->notification = $notification;
        return $this;
    }

}
