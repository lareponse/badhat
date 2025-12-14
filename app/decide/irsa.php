<?php

$event = qp('SELECT * FROM `timeline` ORDER BY `event_year` ASC');
$events = $event->fetchAll(PDO::FETCH_ASSOC);
return ['events' => $events];