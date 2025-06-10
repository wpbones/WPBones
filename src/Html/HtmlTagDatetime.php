<?php

namespace Ondapresswp\WPBones\Html;

class HtmlTagDatetime extends HtmlTag
{

  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
    protected $attributes = [
    'disabled' => null,
    'name'     => null,
    'value'    => null,
    'complete' => null,
    'clear'    => null,
    'now'      => null,
  ];

        /**
         * @return mixed
         */
    public function html()
    {
        ob_start();

        $months = [
      '',
      __('Jan'),
      __('Feb'),
      __('Mar'),
      __('Apr'),
      __('May'),
      __('Jun'),
      __('Jul'),
      __('Aug'),
      __('Sep'),
      __('Oct'),
      __('Nov'),
      __('Dic'),
    ];

        $month  = 0;
        $day    = '';
        $year   = '';
        $hour   = '';
        $minute = '';
        $value  = '';

            if (!empty(wpbones_value($this->value))) {
            if ($this->value == 'now') {
                $month  = date('n');
                $day    = date('d');
                $year   = date('Y');
                $hour   = date('H');
                $minute = date('i');
            }

            if (is_numeric($this->value)) {
                $month  = date('n', $this->value);
                $day    = date('d', $this->value);
                $year   = date('Y', $this->value);
                $hour   = date('H', $this->value);
                $minute = date('i', $this->value);
            }

                if (!is_numeric($this->value)) {
                $month  = date('n', strtotime($this->value));
                $day    = date('d', strtotime($this->value));
                $year   = date('Y', strtotime($this->value));
                $hour   = date('H', strtotime($this->value));
                $minute = date('i', strtotime($this->value));
            }

            $value = sprintf('%s-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
        }

        $container = md5((time() . microtime() . uniqid()));?>
<style>
  .wpbones-input-datetime {
    display: inline-flex;
    align-items: center;
  }
  .wp-bones-datetime-button {
    display: inline-block;
  }

  .wp-bones-datetime-button a,
  .wp-bones-datetime-button a:focus {
    text-decoration: none;
    outline: none;
    -moz-box-shadow: none;
    -webkit-box-shadow: none;
    box-shadow: none;
  }
</style>
<span id="<?php echo $container ?>" class="wpbones-input-datetime">
  <input type="hidden" value="<?php echo $value ?>"
    name="<?php echo $this->name ?>" />
  <select id="<?php echo $this->name ?>"
    name="<?php echo $this->name ?>_month">
    <?php foreach ($months as $key => $value): ?>
    <option<?php selected($key, $month)?> value="<?php echo $key ?>"><?php echo $value ?>
    </option>
    <?php endforeach;?>
  </select>
  <input name="<?php echo $this->name ?>_day"
    value="<?php echo $day ?>" data-type="day" size="2"
    style="width: 3.3rem" type="number" min="1" max="31" />,
  <input name="<?php echo $this->name ?>_year"
    value="<?php echo $year ?>" data-type="year" size="2" min="1"
    style="width: 4.3rem" type="number" /> @
  <input name="<?php echo $this->name ?>_hour" size="2"
    data-type="hour" style="width: 3.3rem" value="<?php echo $hour ?>"
    min="0" max="23" type="number" /> :
  <input name="<?php echo $this->name ?>_minute" size="2"
    data-type="minute" style="width: 3.3rem" min="0" max="59"
    value="<?php echo $minute ?>" type="number" />
  <?php if ($this->complete || $this->clear): ?>
  <span class="wp-bones-datetime-button wp-bones-datetime-clear-button">
    <a href="#">
      <i class="dashicons dashicons-dismiss"></i>
    </a>
  </span>
  <?php endif;?>
<?php if ($this->now): ?>
  <span class="wp-bones-datetime-button wp-bones-datetime-now-button">
    <a href="#">
      <i class="dashicons dashicons-calendar-alt"></i>
    </a>
  </span>
  <?php endif;?>
</span>
<script>
  (function($) {
    String.prototype.lead = function() {
      if (this.length < 2) {
        return "0" + this;
      }

      return this;
    };

    var $container = $("#<?php echo $container ?>"),
      $year = $container.find("input[data-type=year]"),
      $month = $container.find("select"),
      $day = $container.find("input[data-type=day]"),
      $hour = $container.find("input[data-type=hour]"),
      $minute = $container.find("input[data-type=minute]"),
      $hidden = $container.find("input[type=hidden]"),
      $clear = $container.find(".wp-bones-datetime-clear-button a"),
      $now = $container.find(".wp-bones-datetime-now-button a"),
      dataValue = {
        year: $year.val(),
        month: $month.val(),
        day: $day.val(),
        hour: $hour.val(),
        minute: $minute.val()
      },
      complete =                                                                                                 <?php echo empty(wpbones_value($this->complete)) ? "false" : "true" ?> ;

    $hidden.on('wpbones.setdate', function(e, params) {
      var date = new Date((params * 1000));

      $year.val(date.getFullYear());
      $month.val(date.getMonth() + 1);
      $day.val(date.getDate());
      $hour.val(date.getHours());
      $minute.val(date.getMinutes());

      dataValue = {
        year: $year.val(),
        month: $month.val(),
        day: $day.val(),
        hour: $hour.val(),
        minute: $minute.val()
      };

      compact();

    });


    function compact() {
      if (complete) {
        var now = new Date();
        dataValue.year = (dataValue.year.length < 1) ? "" + now.getFullYear() : dataValue.year;
        dataValue.month = (dataValue.month.length < 1) ? "" + (now.getMonth() + 1) : dataValue.month;
        dataValue.day = (dataValue.day.length < 1) ? "" + now.getDate() : dataValue.day;
        dataValue.hour = (dataValue.hour.length < 1) ? "" + now.getHours() : dataValue.hour;
        dataValue.minute = (dataValue.minute.length < 1) ? "" + now.getMinutes() : dataValue.minute;
      }

      $hidden.val(
        dataValue.year + "-" +
        dataValue.month.lead() + "-" +
        dataValue.day.lead() + " " +
        dataValue.hour.lead() + ":" +
        dataValue.minute.lead() + ":00"
      );

      $container.find("input[data-type]").each(function(i, e) {
        $(e).val(dataValue[$(e).data('type')]);
      });

      $container.find("selected").val(dataValue['month']);

    }

    // clear
    $clear.on('click', function(e) {
      e.preventDefault();

      $year.val("");
      $month.val("");
      $day.val("");
      $hour.val("");
      $minute.val("");
      $hidden.val("");

    });

    // now
    $now.on('click', function(e) {
      e.preventDefault();

      var now = new Date();
      dataValue.year = "" + now.getFullYear();
      dataValue.month = "" + (now.getMonth() + 1);
      dataValue.day = "" + now.getDate();
      dataValue.hour = "" + now.getHours();
      dataValue.minute = "" + now.getMinutes();

      $year.val(dataValue.year);
      $month.val(dataValue.month);
      $day.val(dataValue.day);
      $hour.val(dataValue.hour);
      $minute.val(dataValue.minute);

      $hidden.val(
        dataValue.year + "-" +
        dataValue.month.lead() + "-" +
        dataValue.day.lead() + " " +
        dataValue.hour.lead() + ":" +
        dataValue.minute.lead() + ":00"
      );

    });

    // month
    $month.on('change',
      function(e) {
        dataValue['month'] = $(this).find('option:selected').val();
        compact();
      });

    $container.find("input[data-type]")
      .on('change',
        function(e) {
          dataValue[$(this).data('type')] = $(this).val();
          compact();
        }
      );
  }(jQuery));
</script>
<?php

    $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
