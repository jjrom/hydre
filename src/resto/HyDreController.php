<?php

/*
 * HyDre - Hydroweb Distribution server
 * 
 * Copyright 2014 CNES
 * 
 * jerome[dot]gasperi[at]cnes[dot]fr
 * 
 * 
 * This software is governed by thhe CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 * 
 */

/**
 * HyDre - RESTo Hydroweb Controller 
 */
class HyDreController extends RestoController {
    /*
     * HyDre products table model description
     * 
     * See https://github.com/jjrom/hydre/blob/master/installation/hydreDB.sql for table description
     * 
     */

    public static $model = array(
        'identifier' => 'db:identifier',
        'parentIdentifier' => 'hydroweb:',
        'hydretype' => array(
            'dbKey' => 'db:hydretype',
            'type' => 'VARCHAR(1)'
        ),
        'orbitNumber' => 'db:tracknumber',
        'authority' => null,
        'startDate' => 'db:startdate',
        'completionDate' => 'db:completiondate',
        'productType' => 'db:producttype',
        'processingLevel' => 'db:processinglevel',
        'platform' => 'db:platformname',
        'resolution' => null,
        'sensorMode' => null,
        'quicklook' => null,
        'thumbnail' => null,
        'archive' => array(
            'dbKey' => 'db:identifier',
            'template' => '/hydre/hydroweb/{a:1}/$download'
        ),
        'mimetype' => null,
        'updated' => 'db:modifieddate',
        'published' => 'db:publisheddate',
        'keywords' => 'db:keywords',
        'geometry' => 'db:geom',
        'comments' => array(
            'dbKey' => 'db:comments',
            'type' => 'TEXT'
        ),
        'diffusion' => array(
            'dbKey' => 'db:diffusion',
            'type' => 'VARCHAR(20)'
        ),
        'status' => array(
            'dbKey' => 'db:status',
            'type' => 'VARCHAR(20)'
        ),
        'country' => array(
            'dbKey' => 'db:country',
            'type' => 'VARCHAR(50)'
        ),
        'geoidval' => array(
            'dbKey' => 'db:geoidval',
            'type' => 'NUMERIC(12,4)'
        ),
        'geoidref' => array(
            'dbKey' => 'db:geoidref',
            'type' => 'VARCHAR(50)'
        ),
        'river' => array(
            'dbKey' => 'db:river',
            'type' => 'VARCHAR(50)'
        ),
        'lake' => array(
            'dbKey' => 'db:lake',
            'type' => 'VARCHAR(50)'
        ),
        'basin' => array(
            'dbKey' => 'db:basin',
            'type' => 'VARCHAR(50)'
        )
    );

    /*
     * Search filters list
     */
    public static $searchFiltersList = array(
        'searchTerms?',
        'count?',
        'startIndex?',
        'geo:box?',
        'geo:name?',
        'time:start?',
        'time:end?',
        'eo:productType?',
        'hydre:status?',
        'hydre:diffusion?'
    );

    /*
     * Search filters list
     */
    public static $searchFiltersDescription = array(
        'hydre:status' => array(
            'key' => 'status',
            'osKey' => 'status',
            'operation' => '=',
            'keyword' => array(
                'value' => 'status={a:1}'
            )
        ),
        'hydre:diffusion' => array(
            'key' => 'diffusion',
            'osKey' => 'diffusion',
            'operation' => '=',
            'keyword' => array(
                'value' => 'diffusion={a:1}'
            )
        )
        
    );

    /**
     * Process HTTP GET Requests
     */
    public function get() {
        $this->defaultGet();
    }

    /**
     * Process HTTP POST requests
     */
    public function post() {
        
        /*
         * POST is processed by ResourceManager module
         */
        if (!class_exists('HyDreResourceManager')) {
            throw new Exception('Forbidden', 403);
        }
        
        /*
         * Identifier should not be set
         */
        if ($this->request['identifier']) {
            throw new Exception('Forbidden', 403);
        }
        
        /*
         * Read input ASCII hydroweb files (lake and/or river)
         * 
         * See examples :
         *  Lake    : https://github.com/jjrom/hydre/blob/master/examples/L_albert.txt
         *  River   : https://github.com/jjrom/hydre/blob/master/examples/R_bra_bra_JA2_0053_01.txt
         * 
         */
        $resourceManager = new HyDreResourceManager($this);
        $this->response = $resourceManager->create(getFiles(false));
        $this->responseStatus = 200;
        
    }

    /**
     * Process HTTP PUT requests
     */
    public function put() {
        $this->defaultPut();
    }

    /**
     * Process HTTP PUT request
     */
    public function delete() {
        $this->defaultDelete();
    }

    /**
     * Download resource i.e. reconstruct input lake or river file
     * 
     * @param {string} $identifier - identifier of resource to download
     */
    protected function getResource($identifier) {
        
        if (substr($identifier, 0, 1) === 'L') {
            $this->getLakeResource($identifier);
        }
        else if (substr($identifier, 0, 1) === 'R') {
            $this->getRiverResource($identifier);
        }
        else {
            throw new Exception('Not Found', 404);
        }
    }

    /**
     * Download resource i.e. reconstruct input river file
     * 
     * @param {string} $identifier - identifier of resource to download
     */
    protected function getRiverResource($identifier) {

        /*
         * Initialize empty content
         */
        $content = "";

        try {
            $dbh = $this->dbConnector->getConnection();
            if (!$dbh) {
                throw new Exception('Database connection error', 500);
            }
            $rivers = pg_query($dbh, 'SELECT river, basin, geoidref, geoidval, modifieddate, status, diffusion, comments, ST_AsText(geom) as point FROM ' . $this->dbConnector->getSchema() . '.' . $this->dbConnector->getTable() . ' WHERE ' . $this->getModelName('identifier') . "='" . pg_escape_string($identifier) . "'");
            if (!$rivers) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }
            $river = pg_fetch_assoc($rivers);
            $measures = pg_query($dbh, 'SELECT measuredate, height, stdev, hfm, cyclenumber, flag FROM ' . $this->dbConnector->getSchema() . '.measures WHERE parentidentifier=\'' . pg_escape_string($identifier) . "' ORDER BY measuredate ASC");
            if (!$measures) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }

        /*
         * Set header + comments i.e.
         * 
         *  station=yel_yel_JA2_0240_03;river=Yellow_River;basin=Yellow_River;lat=36.4961;lon=116.6148;ref=GGM02C;ref_value=0.000;date=2011/11/15;type=operational;diff=restricted
         */
        list($lon, $lat) = explode(' ', $river['point']);
        $content .= 'station=' . substr($identifier, 2, strlen($identifier) - 1) // Remove the prefix 'R_' 
                . ';river=' . $river['river']
                . ';basin=' . $river['basin']
                . ';lat=' . rtrim($lat, ')')
                . ';lon=' . substr($lon, 6)
                . ';ref=' . $river['geoidref']
                . ';ref_value=' . $river['geoidval']
                . ';date=' . str_replace('-', '/', substr($river['modifieddate'], 0, 10))
                . ';type=' . $river['status']
                . ';diff=' . $river['diffusion']
                . "\n" . $river['comments'];

        /*
         * Loop over all measures
         * 
         *  # Data file format
         *  # (1): decimal year, (2): date = aaaa/mm/dd, (3): time = hh:mn
         *  # (4): height above surface of ref (m), (5): standard deviation from height (m),
         *  # (6): number high frequency measurements to compute the height, (7): cycle number
         *  # undef values=999.999, (8): flag
         *  #
         *  2008.5331 ; 2008/06/11 ; 16:59 ;    75.611 ;    1.426 ;    0 ;  ; 
         */
        while ($measure = pg_fetch_assoc($measures)) {
            list($date, $time) = explode('T', $this->decimalYearToDate($measure['measuredate']));
            $content .= join(';', array($measure['measuredate'], str_replace('-', '/', $date), substr($time, 0, 5), $measure['height'], $measure['stdev'], $measure['hfm'], $measure['cyclenumber'], $measure['flag'])) . "\n";
        }

        /*
         * Close database connection
         */
        pg_close($dbh);

        /*
         * This is a stream - this will bypass RESTo::send() function
         */
        $this->description['forceStream'] = true;

        /*
         * Response should not be empty
         */
        $this->response = 'Download';
        $this->responseStatus = 200;

        /*
         * Stream file in 1024*1024 chunk tiles
         */
        header('HTTP/1.1 200 OK');
        header('Access-Control-Allow-Origin: *');
        header('Content-Disposition: attachment; filename="' . $identifier . '.txt"');
        header('Content-Type: text/plain');
        echo $content;
    }

    /**
     * Download resource i.e. reconstruct input lake file
     * 
     * @param {string} $identifier - identifier of resource to download
     */
    protected function getLakeResource($identifier) {

        /*
         * Initialize empty content
         */
        $content = "";

        try {
            $dbh = $this->dbConnector->getConnection();
            if (!$dbh) {
                throw new Exception('Database connection error', 500);
            }
            $lakes = pg_query($dbh, 'SELECT lake, country, basin, startdate, completiondate, modifieddate, status, diffusion, comments, ST_AsText(geom) as point FROM ' . $this->dbConnector->getSchema() . '.' . $this->dbConnector->getTable() . ' WHERE ' . $this->getModelName('identifier') . "='" . pg_escape_string($identifier) . "'");
            if (!$lakes) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }
            $lake = pg_fetch_assoc($lakes);
            $measures = pg_query($dbh, 'SELECT measuredate, height, stdev, area, volume, flag FROM ' . $this->dbConnector->getSchema() . '.measures WHERE parentidentifier=\'' . pg_escape_string($identifier) . "' ORDER BY measuredate ASC");
            if (!$measures) {
                pg_close($dbh);
                throw new Exception('Database connection error', 500);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }

        /*
         * Set header + comments i.e.
         * 
         *  lake=balkhash;country=Kazakstan;basin=Balkhash;lat= 45.73;lon= 76.42;date=2012/12/12;first_date= 1992.738;last_date= 2011.861;type=operationnal;diff=public
         */
        list($lon, $lat) = explode(' ', $lake['point']);
        $content .= 'lake=' . substr($identifier, 2, strlen($identifier) - 1) // Remove the prefix 'L_' 
                . ';country=' . $lake['country']
                . ';basin=' . $lake['basin']
                . ';lat=' . rtrim($lat, ')')
                . ';lon=' . substr($lon, 6)
                . ';date=' . str_replace('-', '/', substr($lake['modifieddate'], 0, 10))
                . ';first_date=' . str_replace('-', '/', substr($lake['startdate'], 0, 10))
                . ';last_date=' . str_replace('-', '/', substr($lake['completiondate'], 0, 10))
                . ';type=' . $lake['status']
                . ';diff=' . $lake['diffusion']
                . "\n" . $lake['comments'];

        /*
         * Loop over all measures
         *
         *  # data file format
         *  # (1): decimal year, (2): date = yyyy/mm/dd, (3): time = hh:mm
         *  # (4): heigth above surface of ref (m), (5): standard deviation from heigth (m)
         *  # (6): area (km2), (7): volume with respect to volume of first date (km3)
         *  # (8): flag
         *  1992.738 ; 1992/09/26 ; 02:35 ;  341.28 ;    0.01 ;  ;  ; 
         */
        while ($measure = pg_fetch_assoc($measures)) {
            list($date, $time) = explode('T', $this->decimalYearToDate($measure['measuredate']));
            $content .= join(';', array($measure['measuredate'], str_replace('-', '/', $date), substr($time, 0, 5), $measure['height'], $measure['stdev'], $measure['area'], $measure['volume'], $measure['flag'])) . "\n";
        }

        /*
         * Close database connection
         */
        pg_close($dbh);

        /*
         * This is a stream - this will bypass RESTo::send() function
         */
        $this->description['forceStream'] = true;

        /*
         * Response should not be empty
         */
        $this->response = 'Download';
        $this->responseStatus = 200;

        /*
         * Stream file in 1024*1024 chunk tiles
         */
        header('HTTP/1.1 200 OK');
        header('Access-Control-Allow-Origin: *');
        header('Content-Disposition: attachment; filename="' . $identifier . '.txt"');
        header('Content-Type: text/plain');
        echo $content;
    }

    /**
     * Convert a decimal year to ISO8601 timestamp
     * 
     * @param float $dyear
     * @return string
     */
    private function decimalYearToDate($dyear) {

        // Given year
        $year = intval($dyear);

        // Get the number of day in the given year
        $nbOfDaysInYear = date("z", mktime(0, 0, 0, 12, 31, $year)) + 1;

        // Get the day of the given year
        $day = ($dyear - $year) * $nbOfDaysInYear;

        // Convert to unix time
        $unixTime = strToTime($year . '-01-01') + (max(0, $day - 1) * 86400);

        // Returns date as ISO8601
        return date('Y-m-d\TH:i:s', $unixTime);
    }

}
