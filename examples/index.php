<?php

require_once "../vendor/autoload.php";

if(!empty($_POST)) {
    extract($_POST);
    if(!empty($gcmUrl) && !empty($serverApiKey)) {
        $messageArray = [];

        //Targets
        if(!empty($to)) {
            $messageArray['to'] = $to;
        }
        if(!empty($registrationIds)) {
            $messageArray['registration_ids'] = explode("\r\n", $registrationIds);
        }

        //Options
        if(!empty($collapseKey)) {
            $messageArray['collapse_key'] = $collapseKey;
        }
        if(!empty($priority)) {
            $messageArray['priority'] = $priority;
        }
        if(isset($contentAvailable)) {
            $messageArray['content_available'] = (bool) $contentAvailable;
        }
        if(isset($delayWhileIdle)) {
            $messageArray['delay_while_idle'] = (bool) $delayWhileIdle;
        }
        if(isset($timeToLive) && $timeToLive !== '') {
            $messageArray['time_to_live'] = $timeToLive;
        }
        if(!empty($restrictedPackageName)) {
            $messageArray['restricted_package_name'] = $restrictedPackageName;
        }
        if(isset($dryRun)) {
            $messageArray['dry_run'] = (bool) $dryRun;
        }

        //Payload
        if(!empty($data)) {
            $messageArray['data'] = json_decode($data, true);
        }
        if(!empty($notification)) {
            $messageArray['notification'] = json_decode($notification, true);
        }

        $message = \PhpGcmQueue\Message::fromArray($messageArray);
        $response = \PhpGcmQueue\Sender::send($message, $serverApiKey, $gcmUrl);
    } else {
        throw new Exception('Missing required fields');
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>PHP GCM Queue Example</title>
    <meta name="description" content="PHP GCM Queue Example">
    <meta name="author" content="stevetauber">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        input,textarea,select {display: block; width: 50em; margin-bottom: 2em;}
        input[required], .required {border: 1px solid red}
    </style>
</head>

<body>
<form method="post">
    <fieldset>
        <legend>Server Settings</legend>

        <label for="gcmUrl">Google Cloud Messaging URL</label>
        <input id="gcmUrl" name="gcmUrl" type="text" placeholder="Google Cloud Messaging URL" value="<?= isset($gcmUrl) ? $gcmUrl : 'https://gcm-http.googleapis.com/gcm/send' ?>" required>

        <label for="serverApiKey">Server Api Key</label>
        <input id="serverApiKey" name="serverApiKey" type="text" placeholder="found in Google Developer Console" value="<?= isset($serverApiKey) ? $serverApiKey : '' ?>" required>
    </fieldset>
    <fieldset>
        <legend>Message</legend>

        <fieldset class="required">
            <legend>Targets</legend>

            <label for="to">To</label>
            <input id="to" name="to" type="text" placeholder="Registration Id" value="<?= isset($to) ? $to : '' ?>"/>

            <label for="registrationIds">Registration Ids</label>
            <textarea name="registrationIds" rows="8" placeholder="Registration Ids, one per line"><?= !empty($registrationIds) ? implode("\r\n", $registrationIds) : '' ?></textarea>
        </fieldset>
        <fieldset>
            <legend>Options</legend>

            <label for="collapseKey">Collapse Key</label>
            <input id="collapseKey" name="collapseKey" type="text" placeholder="Collapse Key" value="<?= isset($collapseKey) ? $collapseKey : '' ?>">

            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <option value="high"<?= (!empty($priority) && $priority == 'high') ? ' selected' : null ?>>High</option>
                <option value="normal"<?= (!empty($priority) && $priority == 'normal') ? ' selected' : null ?>>Normal</option>
            </select>

            <label for="contentAvailable">Content Available</label>
            <select id="contentAvailable" name="contentAvailable">
                <option value="1"<?= !empty($contentAvailable) ? ' selected' : null ?>>True</option>
                <option value="0"<?= !empty($contentAvailable) ? null : ' selected' ?>>False</option>
            </select>

            <label for="delayWhileIdle">Delay While Idle</label>
            <select id="delayWhileIdle" name="delayWhileIdle">
                <option value="1"<?= !empty($delayWhileIdle) ? ' selected' : null ?>>True</option>
                <option value="0"<?= !empty($delayWhileIdle) ? null : ' selected' ?>>False</option>
            </select>

            <label for="timeToLive">Time to Live</label>
            <input id="timeToLive" name="timeToLive" type="text" placeholder="Between 0 - 2419200 or empty (null)" value="<?= isset($ttl) ? $ttl : '' ?>">

            <label for="restrictedPackageName">Restricted Package Name</label>
            <input id="restrictedPackageName" name="restrictedPackageName" type="text" placeholder="Restricted Package Name" value="<?= isset($restrictedPackageName) ? $restrictedPackageName : '' ?>">

            <label for="dryRun">Dry Run</label>
            <select id="dryRun" name="dryRun">
                <option value="1"<?= !empty($dryRun) ? ' selected' : null ?>>True</option>
                <option value="0"<?= !empty($dryRun) ? null : ' selected' ?>>False</option>
            </select>

        </fieldset>

        <fieldset>
            <legend>Payload</legend>

            <label for="data">Data</label>
            <textarea name="data" rows="8" placeholder="Json Data"><?= !empty($data) ? $data : '' ?></textarea>

            <label for="notification">Notification</label>
            <textarea name="notification" rows="8" placeholder="Json Data"><?= !empty($notification) ? $notification : '' ?></textarea>

        </fieldset>

        <fieldset>
            <legend>Response</legend>

            <label for="results">Results</label>
            <textarea name="results" rows="8" placeholder="Results"><?= isset($response) ? json_encode($response->toArray()) : '' ?></textarea>
        </fieldset>

    <button type="submit">Send</button>
    </fieldset>

</form>
</body>
</html>