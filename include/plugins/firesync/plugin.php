<?php
require_once(INCLUDE_DIR . 'class.plugin.php');

class FireSyncPlugin extends Plugin {
    var $config_class = 'FireSyncPluginConfig';

    function bootstrap() {
        Signal::connect('ticket.created', array($this, 'onTicketEvent'));
        Signal::connect('threadentry.created', array($this, 'onThreadEntry'));
        Signal::connect('ticket.assigned', array($this, 'onTicketAssigned'));
        Signal::connect('ticket.status', array($this, 'onTicketStatus'));
    }

    function onTicketEvent(Ticket $ticket) {
        $payload = array(
            'ticket_id' => $ticket->getId(),
            'status'    => $ticket->getStatus(),
            'created'   => $ticket->getCreateDate(),
        );
        $this->postToFirestore('tickets', $payload);
    }

    function onThreadEntry(ThreadEntry $entry) {
        if ($entry->getThread()->getObjectType() != 'T')
            return;
        $payload = array(
            'ticket_id' => $entry->getThreadId(),
            'poster'    => $entry->getPoster(),
            'message'   => $entry->getBody(),
            'created'   => $entry->getCreateDate(),
        );
        $this->postToFirestore('tickets_comments', $payload);
    }

    function onTicketAssigned($ticket, $assignee) {
        if (!$ticket instanceof Ticket)
            return;
        $name = (is_object($assignee) && method_exists($assignee, 'getName')) ?
            $assignee->getName() : '';
        $payload = array(
            'ticket_id' => $ticket->getId(),
            'type'      => 'assigned',
            'agent'     => $name,
            'created'   => time(),
        );
        $this->postToFirestore('user_notifications', $payload);
    }

    function onTicketStatus($ticket, $status) {
        if (!$ticket instanceof Ticket)
            return;
        $payload = array(
            'ticket_id' => $ticket->getId(),
            'type'      => 'status',
            'status'    => (string)$status,
            'created'   => time(),
        );
        $this->postToFirestore('user_notifications', $payload);
    }

    private function postToFirestore($collection, array $payload) {
        $project = $this->getConfig()->get('project_id');
        $token = getenv('FIREBASE_TOKEN');
        if (!$project || !$token)
            return;

        $url = sprintf('https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents/%s',
            $project, $collection);
        $body = json_encode(array('fields' => $this->buildFields($payload)));
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true
        ));
        curl_exec($ch);
        curl_close($ch);
    }

    private function buildFields(array $data) {
        $fields = array();
        foreach ($data as $k => $v)
            $fields[$k] = array('stringValue' => (string)$v);
        return $fields;
    }
}
