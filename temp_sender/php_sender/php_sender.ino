#include <WiFi.h>
#include <WiFiClient.h>
#include <HTTPClient.h>
#include <DHT.h>

// WiFi credentials
const char *ssid = "Mr.Potatoe";
const char *password = "passwordno";

// Server URL
const char *serverUrl = "http://192.168.200.111/esp32_data_heat/insert_data.php";  // Replace with your XAMPP server IP

DHT dht(26, DHT11);

// Sensor coordinates (Latitude, Longitude)
const char *sensorLatitude = "7.957062";    // Replace with actual latitude
const char *sensorLongitude = "123.527546"; // Replace with actual longitude
const int sensor_id = 1; // Define your sensor ID (it can be any unique number)


void setup() {
  Serial.begin(115200);
  dht.begin();

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("Connected to WiFi");
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;

    // Read temperature and humidity from the DHT sensor
    float temperature = dht.readTemperature();
    float humidity = dht.readHumidity();

    if (!isnan(temperature) && !isnan(humidity)) {
      // Calculate Heat Index using the PAGASA formula
      float heatIndex = calculateHeatIndex(temperature, humidity);
      String alertLevel = determineAlertLevel(heatIndex);

      http.begin(serverUrl);  // Specify the server URL
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");  // Set header for POST request

      // Create POST data including sensor coordinates and alert
      String postData = "sensor_id=" + String(sensor_id) + // include the sensor ID
                        "&temperature=" + String(temperature) +
                        "&humidity=" + String(humidity) +
                        "&heat_index=" + String(heatIndex) +
                        "&alert=" + alertLevel +
                        "&latitude=" + String(sensorLatitude) +
                        "&longitude=" + String(sensorLongitude);

      // Send the POST request
      int httpResponseCode = http.POST(postData);

      // Check response
      if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.println("Response: " + response);
      } else {
        Serial.println("Error in sending POST: " + String(httpResponseCode));
      }

      http.end();  // Close connection
    }

  } else {
    Serial.println("WiFi not connected");
  }

  delay(10000);  // Send data every 10 seconds
}

// Function to calculate heat index (PAGASA standard)
float calculateHeatIndex(float temperature, float humidity) {
  return -8.784695 + 1.61139411 * temperature + 2.338549 * humidity + 
         -0.14611605 * temperature * humidity + 
         -0.012308094 * pow(temperature, 2) + 
         -0.016424828 * pow(humidity, 2) + 
         0.002211732 * pow(temperature, 2) * humidity + 
         0.00072546 * temperature * pow(humidity, 2) + 
         -0.000003582 * pow(temperature, 2) * pow(humidity, 2);
}

// Function to determine alert level based on heat index
String determineAlertLevel(float heatIndex) {
  if (heatIndex < 27) {
    return "Normal";
  } else if (heatIndex >= 27 && heatIndex <= 32) {
    return "Caution";
  } else if (heatIndex >= 33 && heatIndex <= 41) {
    return "Extreme Caution";
  } else if (heatIndex >= 42 && heatIndex <= 51) {
    return "Danger";
  } else {
    return "Extreme Danger";
  }
}
