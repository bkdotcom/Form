<?php

namespace bdk\Form;

/**
 * Handle form alerts
 */
class Alerts
{

    private $alerts = array();

    /**
     * Add an alert
     *
     * Three ways to add
     *   add($alert, $class, $dismissible)
     *   add(array($alert, $class, $dismissible))
     *   add(array('alert'=>'', 'class'=>'', 'dismissible'=>true))
     *
     * @param string  $alert       alert
     * @param string  $class       ('danger'), 'success', 'info', 'warning'
     * @param boolean $dismissible (true)
     *
     * @return void
     */
    public function add($alert, $class = 'danger', $dismissible = true)
    {
        $alertDefault = array(
            'alert' => '',
            'class' => $class,
            'dismissible' => $dismissible,
        );
        if (!\is_array($alert)) {
            $alert = array(
                'alert' => $alert,
            );
        } else {
            foreach (array('alert','class','dismissible') as $i => $key) {
                if (isset($alert[$i])) {
                    $alert[$key] = $alert[$i];
                    unset($alert[$i]);
                }
            }
        }
        $alert = \array_merge($alertDefault, $alert);
        if (\strlen($alert['alert'])) {
            // $this->debug->warn('adding alert', $alert);
            $this->alerts[] = $alert;
        }
    }

    /**
     * Default Alert Builder
     *
     * @return string
     */
    public function buildAlerts()
    {
        $str = '';
        foreach ($this->alerts as $alert) {
            $str = $this->build($alert);
        }
        return $str;
    }

    /**
     * Clear alerts
     *
     * @return void
     */
    public function clear()
    {
        $this->alerts = array();
    }

    /**
     * Get all (raw) alerts
     *
     * @return array
     */
    public function getAll()
    {
        return $this->alerts;
    }

    /**
     * Build an alert
     *
     * @param array $alert 'alert','class','dismissable','framework'
     *
     * @return string
     */
    private function build($alert = array())
    {
        $str = '';
        $alert = \array_merge(array(
            'alert'         => '',
            'dismissible'   => true,
            'class'         => 'danger',        // success info warning danger
            'framework'     => 'bootstrap',
        ), $alert);
        $alert['class'] = 'alert-'.$alert['class'];
        if ($alert['framework'] == 'bootstrap') {
            if ($alert['dismissible']) {
                $str .= '<div class="alert alert-dismissible '.$alert['class'].'" role="alert">'
                    .'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                    .$alert['alert']
                    .'</div>';
            } else {
                $str .= '<div class="alert '.$alert['class'].'" role="alert">'.$alert['alert'].'</div>';
            }
        } else {
            $str .= '<div class="alert '.$alert['class'].'">'.$alert['alert'].'</div>';
        }
        return $str;
    }
}
