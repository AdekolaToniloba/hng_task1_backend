<?php
header('Content-Type: application/json');

// Function to get client IP address
function getClientIP()
{
    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($headers as $key) {
        if (array_key_exists($key, $_SERVER)) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return '127.0.0.1'; // Default to localhost if no valid IP found
}

// Function to get location and temperature
function getLocationAndTemperature($ip)
{
    // Using ipapi.co for geolocation 
    $geo_response = file_get_contents("https://ipapi.co/{$ip}/json/");
    $geo_data = json_decode($geo_response, true);

    $city = $geo_data['city'] ?? 'Unknown';
    $latitude = $geo_data['latitude'] ?? 0;
    $longitude = $geo_data['longitude'] ?? 0;

    // Using OpenWeatherMap API for temperature 
    $api_key = '94135e360224ca98041a5f29efc7ce34'; // Replace with your API key
    $weather_response = file_get_contents("https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&appid={$api_key}&units=metric");
    $weather_data = json_decode($weather_response, true);

    $temperature = $weather_data['main']['temp'] ?? 'Unknown';

    return ['city' => $city, 'temperature' => $temperature];
}

// Main logic
try {
    $visitor_name = $_GET['visitor_name'] ?? 'Guest';
    $client_ip = getClientIP();
    $location_data = getLocationAndTemperature($client_ip);

    $response = [
        'client_ip' => $client_ip,
        'location' => $location_data['city'],
        'greeting' => "Hello, {$visitor_name}! The temperature is {$location_data['temperature']} degrees Celsius in {$location_data['city']}"
    ];

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
