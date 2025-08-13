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

    function onTicketAssigned(Ticket $ticket) {
        $payload = array(
            'ticket_id' => $ticket->getId(),
            'assigned'  => $ticket->getAssigned(),
        );
        $this->postToFirestore('tickets_assigned', $payload);
    }

    function onTicketStatus(Ticket $ticket) {
        $payload = array(
            'ticket_id' => $ticket->getId(),
            'status'    => $ticket->getStatus(),
        );
        $this->postToFirestore('tickets_status', $payload);
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
