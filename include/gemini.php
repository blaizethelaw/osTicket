<?php
class GeminiClient {
    public static function call($prompt) {
        $key = getenv('GEMINI_API_KEY');
        if (!$key)
            return null;
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $key;
        $body = json_encode(array(
            'contents' => array(array('parts' => array(array('text' => $prompt)))))
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true
        ));
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }
}
