<?php
namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Datetime\DateTimePlus;

/**
 * Provides a 'Weather' Block.
 */

#[Block(
  id: "weather_block",
  admin_label: new TranslatableMarkup("Weather Readings"),
  category: new TranslatableMarkup("Weather World")
)]

class Weather extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $json_content = \Drupal::state()->get('weather.last_results');
    $content = json_decode($json_content);
    // dump($content);

    if(empty($content->data)) {
      $output = '<p>No weather data available at this time.</p>';
    } else {
      $datetime = $content->data->{'Air Temperature'}[0]->{'readings'}[0]->{'datetime'};
      $datetime = new DateTimePlus($datetime, new \DateTimeZone('America/New_York'));
      $datetime = $datetime->format('Y-m-d g:ia');
      $air_temp = $content->data->{'Air Temperature'}[0]->{'readings'}[0]->{'value'};
      $air_temp_F = $air_temp * 1.8 + 32;
      $atmospheric_pressure = $content->data->{'Atmospheric Pressure'}[0]->{'readings'}[0]->{'value'};
      $atmospheric_pressure = round($atmospheric_pressure * 10, 2);
      $wind_gust = $content->data->{'Gust Speed'}[0]->{'readings'}[0]->{'value'};
      $wind_gust = round($wind_gust * 1.94384, 2); // convert meters/second to knots
      $wind_dir = $content->data->{'Wind Direction'}[0]->{'readings'}[0]->{'value'};
      //build wind direction map
      if ($wind_dir >= 340 || $wind_dir < 20 ) {
        $wind_dir_txt = 'N';
      }
      if ($wind_dir >= 20 && $wind_dir < 60 ) {
        $wind_dir_txt = 'NE';
      }
      if ($wind_dir >= 60 && $wind_dir < 110 ) {
        $wind_dir_txt = 'E';
      }
      if ($wind_dir >= 110 && $wind_dir < 160 ) {
        $wind_dir_txt = 'SE';
      }
      if ($wind_dir >= 160 && $wind_dir < 200 ) {
        $wind_dir_txt = 'S';
      }
      if ($wind_dir >= 200 && $wind_dir < 250 ) {
        $wind_dir_txt = 'SW';
      }
      if ($wind_dir >= 250 && $wind_dir < 290 ) {
        $wind_dir_txt = 'W';
      }
      if ($wind_dir >= 290 && $wind_dir < 340 ) {
        $wind_dir_txt = 'NW';
      }

      $wind_speed = $content->data->{'Wind Speed'}[0]->{'readings'}[0]->{'value'};
      $wind_speed = round($wind_speed * 1.94384, 2);  // convert meters/second to knots
      $water_temp = $content->data->{'Water Temperature'}[0]->{'readings'}[0]->{'value'};
      $water_temp_F = $water_temp * 1.8 + 32;
      $precipitation = $content->data->{'Precipitation'}[0]->{'readings'}[0]->{'value'};
      $precipitation = round($precipitation / 25.4, 2);  //convert mm to inches
      $water_level = $content->data->{'Water Level'}[0]->{'readings'}[0]->{'value'};
      $water_level = round($water_level / 304.8, 2);  //convert mm to feet

      $output = '<a class="button" href="#" data-open="weather_modal">Current Weather Conditions</a>';
      $output .= '<div class="reveal" id="weather_modal" data-reveal>';
      $output .= '<div class="weather_container"><h3>Current Conditions</h3>';
      $output .= '<div class="row"><span class="weather_label">Date & Time:</span> <span class="weather_val">' . $datetime . '</span></div>';
      $output .= '<div class="row"><span class="weather_label">Air Temperature:</span> <span class="weather_val">' . $air_temp_F. ' &#8457;</span></div>';
      $output .= '<div class="row"><span class="weather_label">Atmospheric Pressure (PRES):</span> <span class="weather_val">' . $atmospheric_pressure. ' mbar</span></div>';
      $output .= '<div class="row"><span class="weather_label">Wind Direction (WDIR):</span> <span class="weather_val">' . $wind_dir_txt . ' ('. $wind_dir. '&#176;)</span></div>';
      $output .= '<div class="row"><span class="weather_label">Wind Speed (WSPD):</span> <span class="weather_val">' . $wind_speed. ' kts</span></div>';
      $output .= '<div class="row"><span class="weather_label">Wind Gust (GST):</span> <span class="weather_val">' . $wind_gust. ' kts</span></div>';
      $output .= '<div class="row"><span class="weather_label">Water Temperature:</span> <span class="weather_val">' . $water_temp_F. ' &#8457;</span></div>';
      $output .= '<div class="row"><span class="weather_label">Water Level:</span> <span class="weather_val">' . $water_level. ' ft</span></div>';
      $output .= '<div class="row"><span class="weather_label">Precipitation:</span> <span class="weather_val">' . $precipitation. ' in</span></div>';
      $output .= '<p>These readings come from an underwater weather sensor at the lighthouse.</p></div>';
      $output .= '<button class="close-button" data-close aria-label="Close modal" type="button"><span aria-hidden="true">&times;</span></button></div>';
    }

    return [
      '#markup' => $output,
      '#attached' => ['library' => 'weather/drupal.weather'],
      '#allowed_tags' => ['a', 'div', 'span', 'button','h3','p'],
    ];
  }

  public function getCacheMaxAge() {
    return 1;
  }
}
