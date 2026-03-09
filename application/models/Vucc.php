<?php

class VUCC extends CI_Model
{

	private $logbooks_locations_array;
	public function __construct()
	{
		$this->load->model('logbooks_model');
		$this->logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
	}

    /*
     *  Fetches worked and confirmed gridsquare on each band and total
     */
    function get_vucc_array($data) {
        $vuccArray = $this->fetchVucc($data);

        if (isset($vuccArray)) {
            return $vuccArray;
        } else {
            return 0;
        }
    }

    /*
     * Builds the array to display worked/confirmed vucc on award page
     */
    function fetchVucc($data) {
        // Use associative arrays for O(1) lookups instead of O(n) in_array()
        $totalGridWorked = [];
        $totalGridConfirmed = [];
        $workedGrids = [];
        $confirmedGrids = [];

        // Create a lookup array for valid bands
        $validBands = array_flip($data['worked_bands']);

        // Get combined data (2 queries instead of 4 per band)
        $combinedData = $this->get_vucc_combined_data_all_bands();

        // Process col_gridsquare data
        if (!empty($combinedData['gridsquare'])) {
            foreach ($combinedData['gridsquare'] as $row) {
                $grid = $row['gridsquare'];
                $band = $row['col_band'];

                // Skip if this band is not in our requested bands list
                if (!isset($validBands[$band])) {
                    continue;
                }

                // Always add to worked
                $totalGridWorked[$grid] = true;
                if (!isset($workedGrids[$band][$grid])) {
                    $workedGrids[$band][$grid] = true;
                }

                // Add to confirmed if flagged
                if ($row['confirmed']) {
                    $totalGridConfirmed[$grid] = true;
                    if (!isset($confirmedGrids[$band][$grid])) {
                        $confirmedGrids[$band][$grid] = true;
                    }
                }
            }
        }

        // Process col_vucc_grids data
        if (!empty($combinedData['vucc_grids'])) {
            foreach ($combinedData['vucc_grids'] as $row) {
                $grids = explode(",", $row['col_vucc_grids']);
                $band = $row['col_band'];

                // Skip if this band is not in our requested bands list
                if (!isset($validBands[$band])) {
                    continue;
                }

                foreach ($grids as $key) {
                    $grid_four = strtoupper(substr(trim($key), 0, 4));

                    // Always add to worked
                    $totalGridWorked[$grid_four] = true;
                    if (!isset($workedGrids[$band][$grid_four])) {
                        $workedGrids[$band][$grid_four] = true;
                    }

                    // Add to confirmed if flagged
                    if ($row['confirmed']) {
                        $totalGridConfirmed[$grid_four] = true;
                        if (!isset($confirmedGrids[$band][$grid_four])) {
                            $confirmedGrids[$band][$grid_four] = true;
                        }
                    }
                }
            }
        }

        // Build the result array with counts per band
        $vuccArray = [];
        foreach ($data['worked_bands'] as $band) {
            $vuccArray[$band]['worked'] = isset($workedGrids[$band]) ? count($workedGrids[$band]) : 0;
            $vuccArray[$band]['confirmed'] = isset($confirmedGrids[$band]) ? count($confirmedGrids[$band]) : 0;
        }

        $vuccArray['All']['worked'] = count($totalGridWorked);
        $vuccArray['All']['confirmed'] = count($totalGridConfirmed);

        if ($vuccArray['All']['worked'] == 0) {
            return null;
        }

        return $vuccArray;
    }

    /*
     *  Gets the grid from col_vucc_grids
     * $band = the band chosen
     * $confirmationMethod - qsl, lotw or both, use anything else to skip confirmed
     */
    function get_vucc_summary_col_vucc($band, $confirmationMethod) {

        if (!$this->logbooks_locations_array) {
            return null;
        }

		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

        $sql = "select distinct col_vucc_grids
            from " . $this->config->item('table_name') .
            " where station_id in (" . $location_list . ")" .
            " and col_vucc_grids <> '' ";

        if ($confirmationMethod == 'both') {
            $sql .= " and (col_qsl_rcvd='Y' or col_lotw_qsl_rcvd='Y')";
        }
        else if ($confirmationMethod == 'qsl') {
            $sql .= " and col_qsl_rcvd='Y'";
        }
        else if ($confirmationMethod == 'lotw') {
            $sql .= " and col_lotw_qsl_rcvd='Y'";
        }

        if ($band != 'All') {
            if ($band == 'SAT') {
                $sql .= " and col_prop_mode ='" . $band . "'";
            } else {
                $sql .= " and col_prop_mode !='SAT'";
                $sql .= " and col_band ='" . $band . "'";
            }
        }

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /*
     * Gets the grid from col_gridsquare
     * $band = the band chosen
     * $confirmationMethod - qsl, lotw or both, use anything else to skip confirmed
     */
    function get_vucc_summary($band, $confirmationMethod) {
	    if (!$this->logbooks_locations_array) {
		    return null;
	    }

	    $location_list = "'".implode("','",$this->logbooks_locations_array)."'";

	    $sql = "select distinct upper(substring(log.col_gridsquare, 1, 4)) gridsquare
		    from " . $this->config->item('table_name') . " log".
		    " where log.station_id in (" . $location_list . ")" .
		    " and log.col_gridsquare <> ''";

	    if ($confirmationMethod == 'both') {
		    $sql .= " and (log.col_qsl_rcvd='Y' or log.col_lotw_qsl_rcvd='Y')";
	    } else if ($confirmationMethod == 'qsl') {
		    $sql .= " and log.col_qsl_rcvd='Y'";
	    } else if ($confirmationMethod == 'lotw') {
		    $sql .= " and log.col_lotw_qsl_rcvd='Y'";
	    }

	    if ($band != 'All') {
		    if ($band == 'SAT') {
			    $sql .= " and log.col_prop_mode ='" . $band . "'";
		    } else {
			    $sql .= " and log.col_prop_mode !='SAT'";
			    $sql .= " and log.col_band ='" . $band . "'";
		    }
	    } else {
		    $sql .= " and log.col_prop_mode !='SAT'";
	    }
	    $query = $this->db->query($sql);

	    return $query->result_array();
    }

    /*
     * Makes a list of all gridsquares on chosen band with info about lotw and qsl
     * Optimized to fetch all callsigns in a single query instead of one per grid
     */
    function vucc_details($band, $type) {
        // Get combined data for the specific band
        $bandData = $this->get_vucc_band_data($band);

        if (empty($bandData['gridsquare']) && empty($bandData['vucc_grids'])) {
            return 0;
        }

        $vuccBand = [];

        // Process col_gridsquare data
        foreach ($bandData['gridsquare'] as $row) {
            $grid = $row['gridsquare'];
            $qsl = $row['qsl_confirmed'] ? 'Y' : '';
            $lotw = $row['lotw_confirmed'] ? 'Y' : '';
            $confirmed = $row['confirmed'];

            // Filter based on type
            if ($type == 'worked' && $confirmed) {
                continue; // Skip confirmed grids when showing only worked
            }
            if ($type == 'confirmed' && !$confirmed) {
                continue; // Skip worked grids when showing only confirmed
            }

            if (!isset($vuccBand[$grid])) {
                $vuccBand[$grid]['qsl'] = $qsl;
                $vuccBand[$grid]['lotw'] = $lotw;
            } else {
                // Update confirmation status if already exists
                if ($qsl) $vuccBand[$grid]['qsl'] = $qsl;
                if ($lotw) $vuccBand[$grid]['lotw'] = $lotw;
            }
        }

        // Process col_vucc_grids data
        foreach ($bandData['vucc_grids'] as $row) {
            $grids = explode(",", $row['col_vucc_grids']);
            $qsl = $row['qsl_confirmed'] ? 'Y' : '';
            $lotw = $row['lotw_confirmed'] ? 'Y' : '';
            $confirmed = $row['confirmed'];

            foreach ($grids as $key) {
                $grid_four = strtoupper(substr(trim($key), 0, 4));

                // Filter based on type
                if ($type == 'worked' && $confirmed) {
                    continue; // Skip confirmed grids when showing only worked
                }
                if ($type == 'confirmed' && !$confirmed) {
                    continue; // Skip worked grids when showing only confirmed
                }

                if (!isset($vuccBand[$grid_four])) {
                    $vuccBand[$grid_four]['qsl'] = $qsl;
                    $vuccBand[$grid_four]['lotw'] = $lotw;
                } else {
                    // Update confirmation status if already exists
                    if ($qsl) $vuccBand[$grid_four]['qsl'] = $qsl;
                    if ($lotw) $vuccBand[$grid_four]['lotw'] = $lotw;
                }
            }
        }

        // Get all callsigns for all grids in a SINGLE query
        $gridCallsigns = $this->get_grid_callsigns_batch(array_keys($vuccBand), $band);

        // Add callsign details for each grid from the batched result
        foreach ($vuccBand as $grid => $data) {
            $callsignlist = '';
            if (isset($gridCallsigns[$grid])) {
                foreach ($gridCallsigns[$grid] as $call) {
                    $callsignlist .= $call . '<br/>';
                }
            }
            $vuccBand[$grid]['call'] = $callsignlist;
        }

        if (!isset($vuccBand)) {
            return 0;
        } else {
            ksort($vuccBand);
            return $vuccBand;
        }
    }

    /*
     * Fetches callsigns for multiple grids in a single query
     * Returns array indexed by 4-character grid with list of callsigns
     */
    private function get_grid_callsigns_batch($grids, $band) {
        if (empty($grids) || !$this->logbooks_locations_array) {
            return [];
        }

        $inPlaceholders = str_repeat('?,', count($this->logbooks_locations_array) - 1) . '?';
        $bindings = array_merge($this->logbooks_locations_array);

        // Build band condition
        if ($band == 'SAT') {
            $bandCondition = " and col_prop_mode = ?";
            $bindings[] = $band;
        } else {
            $bandCondition = " and col_prop_mode != ? and col_band = ?";
            $bindings[] = 'SAT';
            $bindings[] = $band;
        }

        // Fetch all QSOs for this band - we'll filter by grids in PHP
        // This is much more efficient than one query per grid
        $sql = "SELECT COL_CALL, col_gridsquare, col_vucc_grids
            FROM " . $this->config->item('table_name') . "
            WHERE station_id IN (" . $inPlaceholders . ")
                AND (col_gridsquare <> '' OR col_vucc_grids <> '')"
            . $bandCondition;

        $query = $this->db->query($sql, $bindings);
        $result = $query->result_array();

        // Group callsigns by grid in PHP
        $gridCallsigns = [];
        $gridsLookup = array_flip($grids); // For O(1) lookups

        foreach ($result as $row) {
            // Process col_gridsquare
            if (!empty($row['col_gridsquare'])) {
                $grid_four = strtoupper(substr($row['col_gridsquare'], 0, 4));
                if (isset($gridsLookup[$grid_four])) {
                    if (!isset($gridCallsigns[$grid_four])) {
                        $gridCallsigns[$grid_four] = [];
                    }
                    // Avoid duplicate callsigns for the same grid
                    if (!in_array($row['COL_CALL'], $gridCallsigns[$grid_four])) {
                        $gridCallsigns[$grid_four][] = $row['COL_CALL'];
                    }
                }
            }

            // Process col_vucc_grids (comma-separated list)
            if (!empty($row['col_vucc_grids'])) {
                $vuccGrids = explode(",", $row['col_vucc_grids']);
                foreach ($vuccGrids as $vuccGrid) {
                    $grid_four = strtoupper(substr(trim($vuccGrid), 0, 4));
                    if (isset($gridsLookup[$grid_four])) {
                        if (!isset($gridCallsigns[$grid_four])) {
                            $gridCallsigns[$grid_four] = [];
                        }
                        // Avoid duplicate callsigns for the same grid
                        if (!in_array($row['COL_CALL'], $gridCallsigns[$grid_four])) {
                            $gridCallsigns[$grid_four][] = $row['COL_CALL'];
                        }
                    }
                }
            }
        }

        return $gridCallsigns;
    }

    /*
     * Fetches VUCC data for a specific band with QSL/LoTW confirmation details
     * Returns data in a single query per band
     */
    private function get_vucc_band_data($band) {
        if (!$this->logbooks_locations_array) {
            return ['gridsquare' => [], 'vucc_grids' => []];
        }

        $results = ['gridsquare' => [], 'vucc_grids' => []];
        $inPlaceholders = str_repeat('?,', count($this->logbooks_locations_array) - 1) . '?';

        // Query 1: Get col_gridsquare data for the band
        $bindings1 = array_merge($this->logbooks_locations_array);

        if ($band == 'SAT') {
            $bandCondition1 = " and log.col_prop_mode = ?";
            $bindings1[] = $band;
        } else {
            $bandCondition1 = " and log.col_prop_mode != ? and log.col_band = ?";
            $bindings1[] = 'SAT';
            $bindings1[] = $band;
        }

        $sql1 = "SELECT
            DISTINCT UPPER(SUBSTRING(col_gridsquare, 1, 4)) as gridsquare,
            MAX(CASE WHEN col_qsl_rcvd='Y' THEN 1 ELSE 0 END) as qsl_confirmed,
            MAX(CASE WHEN col_lotw_qsl_rcvd='Y' THEN 1 ELSE 0 END) as lotw_confirmed,
            MAX(CASE WHEN (col_qsl_rcvd='Y' OR col_lotw_qsl_rcvd='Y') THEN 1 ELSE 0 END) as confirmed
            FROM " . $this->config->item('table_name') . " log
            WHERE log.station_id IN (" . $inPlaceholders . ")
                AND log.col_gridsquare <> ''"
            . $bandCondition1 . "
            GROUP BY UPPER(SUBSTRING(col_gridsquare, 1, 4))";

        $query1 = $this->db->query($sql1, $bindings1);
        if ($query1->num_rows() > 0) {
            $results['gridsquare'] = $query1->result_array();
        }

        // Query 2: Get col_vucc_grids data for the band
        $bindings2 = array_merge($this->logbooks_locations_array);

        if ($band == 'SAT') {
            $bandCondition2 = " and col_prop_mode = ?";
            $bindings2[] = $band;
        } else {
            $bandCondition2 = " and col_prop_mode != ? and col_band = ?";
            $bindings2[] = 'SAT';
            $bindings2[] = $band;
        }

        $sql2 = "SELECT
            DISTINCT col_vucc_grids,
            MAX(CASE WHEN col_qsl_rcvd='Y' THEN 1 ELSE 0 END) as qsl_confirmed,
            MAX(CASE WHEN col_lotw_qsl_rcvd='Y' THEN 1 ELSE 0 END) as lotw_confirmed,
            MAX(CASE WHEN (col_qsl_rcvd='Y' OR col_lotw_qsl_rcvd='Y') THEN 1 ELSE 0 END) as confirmed
            FROM " . $this->config->item('table_name') . "
            WHERE station_id IN (" . $inPlaceholders . ")
                AND col_vucc_grids <> ''"
            . $bandCondition2 . "
            GROUP BY col_vucc_grids";

        $query2 = $this->db->query($sql2, $bindings2);
        if ($query2->num_rows() > 0) {
            $results['vucc_grids'] = $query2->result_array();
        }

        return $results;
    }

	function grid_detail($gridsquare, $band) {
		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";
        $sql = "select COL_CALL from " . $this->config->item('table_name') .
                " where station_id in (" . $location_list . ")" .
                " and (col_gridsquare like '" . $gridsquare. "%'
                    or col_vucc_grids like '%" . $gridsquare. "%')";

        if ($band != 'All') {
            if ($band == 'SAT') {
                $sql .= " and col_prop_mode ='" . $band . "'";
            } else {
                $sql .= " and col_prop_mode !='SAT'";
                $sql .= " and col_band ='" . $band . "'";
            }
        }

        return $this->db->query($sql);
    }

    private function get_vucc_combined_data($band = 'All') {
        if (!$this->logbooks_locations_array) {
            return ['gridsquare' => [], 'vucc_grids' => []];
        }

        $results = ['gridsquare' => [], 'vucc_grids' => []];

        $inPlaceholders = str_repeat('?,', count($this->logbooks_locations_array) - 1) . '?';

        // Query 1: Get col_gridsquare data with worked/confirmed status
        $bindings1 = array_merge($this->logbooks_locations_array);
        $bandCondition1 = '';

        if ($band != 'All') {
            if ($band == 'SAT') {
                $bandCondition1 = " and log.col_prop_mode = ?";
                $bindings1[] = $band;
            } else {
                $bandCondition1 = " and log.col_prop_mode != ? and log.col_band = ?";
                $bindings1[] = 'SAT';
                $bindings1[] = $band;
            }
        } else {
            $bandCondition1 = " and log.col_prop_mode != ?";
            $bindings1[] = 'SAT';
        }

        $sql1 = "SELECT
            DISTINCT UPPER(SUBSTRING(col_gridsquare, 1, 4)) as gridsquare,
            MAX(CASE WHEN (col_qsl_rcvd='Y' OR col_lotw_qsl_rcvd='Y') THEN 1 ELSE 0 END) as confirmed
            FROM " . $this->config->item('table_name') . " log
            WHERE log.station_id IN (" . $inPlaceholders . ")
                AND log.col_gridsquare <> ''"
            . $bandCondition1 . "
            GROUP BY UPPER(SUBSTRING(col_gridsquare, 1, 4))";

        $query1 = $this->db->query($sql1, $bindings1);
        if ($query1->num_rows() > 0) {
            $results['gridsquare'] = $query1->result_array();
        }

        // Query 2: Get col_vucc_grids data with worked/confirmed status
        // Note: col_vucc_grids has NO band filter when band='All' (includes SAT)
        $bindings2 = array_merge($this->logbooks_locations_array);
        $bandCondition2 = '';

        if ($band != 'All') {
            if ($band == 'SAT') {
                $bandCondition2 = " and col_prop_mode = ?";
                $bindings2[] = $band;
            } else {
                $bandCondition2 = " and col_prop_mode != ? and col_band = ?";
                $bindings2[] = 'SAT';
                $bindings2[] = $band;
            }
        }
        // When band='All', NO band filter is added (includes all prop_mode including SAT)

        $sql2 = "SELECT
            DISTINCT col_vucc_grids,
            MAX(CASE WHEN (col_qsl_rcvd='Y' OR col_lotw_qsl_rcvd='Y') THEN 1 ELSE 0 END) as confirmed
            FROM " . $this->config->item('table_name') . "
            WHERE station_id IN (" . $inPlaceholders . ")
                AND col_vucc_grids <> ''"
            . $bandCondition2 . "
            GROUP BY col_vucc_grids";

        $query2 = $this->db->query($sql2, $bindings2);
        if ($query2->num_rows() > 0) {
            $results['vucc_grids'] = $query2->result_array();
        }

        return $results;
    }

    /*
     * Fetches VUCC data for ALL bands in 2 queries (optimized)
     * Similar approach to CQ model's getCqZoneData()
     * Returns data with band information included for processing
     */
    private function get_vucc_combined_data_all_bands() {
        if (!$this->logbooks_locations_array) {
            return ['gridsquare' => [], 'vucc_grids' => []];
        }

        $results = ['gridsquare' => [], 'vucc_grids' => []];
        $inPlaceholders = str_repeat('?,', count($this->logbooks_locations_array) - 1) . '?';

        // Query 1: Get col_gridsquare data for ALL bands with worked/confirmed status
        // GROUP BY both gridsquare and band to get per-band statistics
        $bindings1 = array_merge($this->logbooks_locations_array);

        $sql1 = "SELECT
            DISTINCT UPPER(SUBSTRING(col_gridsquare, 1, 4)) as gridsquare,
            col_band,
            MAX(CASE WHEN (col_qsl_rcvd='Y' OR col_lotw_qsl_rcvd='Y') THEN 1 ELSE 0 END) as confirmed
            FROM " . $this->config->item('table_name') . " log
            WHERE log.station_id IN (" . $inPlaceholders . ")
                AND log.col_gridsquare <> ''
                AND log.col_prop_mode != 'SAT'
            GROUP BY UPPER(SUBSTRING(col_gridsquare, 1, 4)), col_band";

        $query1 = $this->db->query($sql1, $bindings1);
        if ($query1->num_rows() > 0) {
            $results['gridsquare'] = $query1->result_array();
        }

        // Query 2: Get col_vucc_grids data for ALL bands with worked/confirmed status
        $bindings2 = array_merge($this->logbooks_locations_array);

        $sql2 = "SELECT
            DISTINCT col_vucc_grids,
            col_band,
            MAX(CASE WHEN (col_qsl_rcvd='Y' OR col_lotw_qsl_rcvd='Y') THEN 1 ELSE 0 END) as confirmed
            FROM " . $this->config->item('table_name') . "
            WHERE station_id IN (" . $inPlaceholders . ")
                AND col_vucc_grids <> ''
                AND col_prop_mode != 'SAT'
            GROUP BY col_vucc_grids, col_band";

        $query2 = $this->db->query($sql2, $bindings2);
        if ($query2->num_rows() > 0) {
            $results['vucc_grids'] = $query2->result_array();
        }

        return $results;
    }

    /*
    * Builds the array to display worked/confirmed vucc on dashboard page
    */
    function fetchVuccSummary($band = 'All') {
        // Use associative arrays for O(1) lookups instead of O(n) in_array()
        $totalGridWorked = [];
        $totalGridConfirmed = [];

        // Get combined data (2 queries instead of 4)
        $data = $this->get_vucc_combined_data($band);

        // Process col_gridsquare data
        if (!empty($data['gridsquare'])) {
            foreach ($data['gridsquare'] as $row) {
                $grid = $row['gridsquare'];
                // Always add to worked
                $totalGridWorked[$grid] = true;
                // Add to confirmed if flagged
                if ($row['confirmed']) {
                    $totalGridConfirmed[$grid] = true;
                }
            }
        }

        // Process col_vucc_grids data
        if (!empty($data['vucc_grids'])) {
            foreach ($data['vucc_grids'] as $row) {
                $grids = explode(",", $row['col_vucc_grids']);
                foreach ($grids as $key) {
                    $grid_four = strtoupper(substr(trim($key), 0, 4));
                    // Always add to worked
                    $totalGridWorked[$grid_four] = true;
                    // Add to confirmed if flagged
                    if ($row['confirmed']) {
                        $totalGridConfirmed[$grid_four] = true;
                    }
                }
            }
        }

        $vuccArray[$band]['worked'] = count($totalGridWorked);
        $vuccArray[$band]['confirmed'] = count($totalGridConfirmed);

        return $vuccArray;
    }
}
?>
