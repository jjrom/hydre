<?php

/*
 * HyDre - Hydroweb Distribution server
 * 
 * Copyright 2014 CNES
 * 
 * jerome[dot]gasperi[at]cnes[dot]fr
 * 
 * 
 * This software is governed by the CeCILL-B license under French law and
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
 * 
 * HyDre Resource manager module
 * 
 * This class allows to manage resource from HyDre collection (see http://github.com/jjrom/hydre)
 * 
 */
class HyDreResourceManager extends ResourceManager {

    /**
     * Create a new resource in HyDre database from
     * hydroweb files
     * 
     * @param array $files - array of POSTED text files
     * @return type
     * @throws Exception
     */
    public function create($files = array()) {
        
        if (!$this->dbh) {
            throw new Exception('Database connection error', 500);
        }
        
        /*
         * Only authenticated user can post files
         * TODO - set authorization level within database (i.e. canPost, canPut, canDelete, etc. ?)
         */
        if (!$this->Controller->getParent()->checkAuth()) {
            throw new Exception('Unauthorized', 401);
        }
        
        if (!is_array($files)) {
            throw new Exception('Invalid posted file(s)', 500);
        }

        /*
         * Nothing to POST
         */
        if (count($files) === 0) {
            throw new Exception('Nothing to post', 200);
        }

        /*
         * Ingest posted files
         * 
         * !!! IMPORTANT !!!
         * 
         * Input file type - lake or river - detection is based on
         * header content, assuming that lake file header starts with 'lake'
         * and river file header starts with 'station'
         * 
         */
        $totalP = 0;
        $totalM = 0;
        for ($i = 0, $l = count($files); $i < $l; $i++) {
            if (is_array($files[$i]) && $files[$i][0]) {
                if (substr($files[$i][0], 0, 4) === 'lake') {
                    $result = $this->ingestLakeResource($files[$i]);
                    $totalP ++;
                }
                else if (substr($files[$i][0], 0, 7) === 'station') {
                    $result = $this->ingestRiverResource($files[$i]);
                    $totalP ++;
                }
                else {
                    continue;
                }
                
                $totalM += $result['measures'];
            }
        }  
        
        return array('Status' => 'success', 'Message' => $totalP . ' resources (with ' . $totalM . ' height measures) inserted');
        
    }

    /**
     * Ingest river file
     * 
     * File example
     * 
     *  station=bra_bra_JA2_0053_01;river=Brahmaputra;basin=Brahmaputra;lat=26.7606;lon=93.4872;ref=GGM02C;ref_value=0.000;date=2011/11/15;type=operational;diff=restricted
     *  #
     *  # water height from satellite altimetry : Jason2 Track 0053
     *  # corrections applied : solid earth tide, pole tide, ionospheric delay
     *  #                       wet and dry tropospheric delay
     *  # The heights are computed above a geoid given in the header (ref, ref_value (m))
     *  #
     *  # Data file format
     *  # (1): decimal year, (2): date = aaaa/mm/dd, (3): time = hh:mn
     *  # (4): height above surface of ref (m), (5): standard deviation from height (m),
     *  # (6): number high frequency measurements to compute the height, (7): cycle number
     *  # undef values=999.999, (8): flag
     *  #
     *  2008.5331 ; 2008/06/11 ; 16:59 ;    75.611 ;    1.426 ;    0 ;  ; 
     *  2008.5602 ; 2008/06/21 ; 14:32 ;    76.072 ;    0.163 ;    0 ;  ; 
     * 
     * @param array $file - Above file split within an array by $this->create function (one line per array entry)
     */
    private function ingestRiverResource($file) {

        /*
         * Initialize key/value for products table
         */
        $productsValue = array(
            'hydretype' => "'R'",
            'publisheddate' => 'now()'
        );
        
        /*
         * Initialize info from header
         * 
         *  station=yel_yel_JA2_0240_03;river=Yellow_River;basin=Yellow_River;lat=36.4961;lon=116.6148;ref=GGM02C;ref_value=0.000;date=2011/11/15;type=operational;diff=restricted
         */
        $infos = explode(';', rtrim($file[0]));
        foreach ($infos as $info) {

            $kvp = explode('=', $info);
            $kvp[0] = trim($kvp[0]);
            $kvp[1] = trim($kvp[1]);

            /*
             * Station name format is T_BBB_FFF_XXX_CCCC_xx
             */
            if ($kvp[0] === 'station') {

                $parentidentifier = 'R_' . $kvp[1];
                $parts = explode('_', $parentidentifier);
                $basin = $this->getRiverOrBasinName($parts[1]) ? $this->getRiverOrBasinName($parts[1]) : $parts[1];
                $river = $this->getRiverOrBasinName($parts[2]) ? $this->getRiverOrBasinName($parts[2]) : $parts[2];
                
                $productsValue['identifier'] = '\'' . $parentidentifier . '\'';
                $productsValue['basin'] = '\'' . pg_escape_string($basin) . '\'';
                $productsValue['river'] = '\'' . pg_escape_string($river) . '\'';
                $productsValue['platformname'] = '\'' . pg_escape_string($this->getSatelliteName($parts[3])) . '\'';
                $productsValue['tracknumber'] = intval($parts[4]);
                $productsValue['producttype'] = '\'WATERHEIGHT\'';
                $productsValue['processinglevel'] = '\'LEVEL3B\'';
            }
            else if ($kvp[0] === 'lat') {
                $latitude = floatval($kvp[1]);
            }
            else if ($kvp[0] === 'lon') {
                $longitude = floatval($kvp[1]);
            }
            else if ($kvp[0] === 'ref') {
                $productsValue['geoidref'] = '\'' . pg_escape_string($kvp[1]) . '\'';
            }
            else if ($kvp[0] === 'ref_value') {
                $productsValue['geoidval'] = pg_escape_string($kvp[1]);
            }
            else if ($kvp[0] === 'date') {
                $productsValue['modifieddate'] = '\'' . pg_escape_string(str_replace('/', '-', $kvp[1])) . '\'';
            }
            else if ($kvp[0] === 'type') {
                $status = $kvp[1];
                $productsValue['status'] = '\'' . pg_escape_string($status) . '\'';
            }
            else if ($kvp[0] === 'diff') {
                $productsValue['diffusion'] = '\'' . pg_escape_string($kvp[1]) . '\'';
            }
            
            /*
             * Keywords :
             *  add basin and river (read within header)
             *  add country and continent (call iTag)
             */
            $radius = 0.01;
            $lonmin = $longitude - $radius;
            $latmin = $latitude - $radius;
            $lonmax = $longitude + $radius;
            $latmax = $latitude + $radius;
            $wkt = 'POLYGON((' . $lonmin . ' ' . $latmin . ',' . $lonmin . ' ' . $latmax . ',' . $lonmax . ' ' . $latmax . ',' . $lonmax . ' ' . $latmin . ',' . $lonmin . ' ' . $latmin . '))';
            $keywords = $this->getTags($wkt, 'countries=true&continents=true');
            
            if ($basin) {
                array_push($keywords, $this->quoteForHstore('basin:' . strtolower($basin)) . '=> NULL');
            }
            if ($river) {
                array_push($keywords, $this->quoteForHstore('river:' . strtolower($river)) . '=> NULL');
            }
            $productsValue['keywords'] = '\'' . join(',', $keywords) . '\'';
            
            /*
             * Geometry
             */
            $productsValue['geom'] = 'ST_GeomFromText(\'POINT(' . $longitude . ' ' . $latitude . ')\', 4326)';
            
        }

        /*
         * Prepare statement
         */
        pg_query($this->dbh, 'BEGIN');
        
        /*
         * Remove previous measures attached to $identifier
         */
        pg_query($this->dbh,'DELETE FROM ' . $this->Controller->getDbConnector()->getSchema() . '.' . 'measures WHERE parentidentifier=\'' . pg_escape_string($parentidentifier) . '\'');
            
        /*
         * Retrieve measures
         */
        // $ordernumber = $parts[5]; // Not used ?
        $startdate = 99999;
        $completiondate = -99999;
        $comments = '';
        $count = 0;
        for ($i = 1, $l = count($file); $i < $l; $i++) {

            $line = $file[$i];

            /*
             * Comments start with '#'
             */
            if (substr(trim($line), 0, 1) === '#') {
                $comments .= $line;
                continue;
            }
            /*
             * Skip empty lines
             */
            else if (trim($line) === '') {
                continue;
            }

            /*
             * Data
             * 
             *  # Data file format
             *  # (1): decimal year, (2): date = aaaa/mm/dd, (3): time = hh:mn
             *  # (4): height above surface of ref (m), (5): standard deviation from height (m),
             *  # (6): number high frequency measurements to compute the height, (7): cycle number
             *  # undef values=999.999, (8): flag
             *  #
             *  2008.5331 ; 2008/06/11 ; 16:59 ;    75.611 ;    1.426 ;    0 ;  ; 
             *  ...etc...
             */
            $data = explode(';', rtrim($line));

            $measuredate = floatval(trim($data[0]));
            $startdate = min($startdate, $measuredate);
            $completiondate = max($completiondate, $measuredate);
            
            /*
             * Table measures
             * 
             * See hydreDB.sql for fields description
             */
            $fieldsValues = array(
                'identifier' => "'" . pg_escape_string($parentidentifier . ':' . $measuredate) . "'",
                'parentidentifier' => "'" . pg_escape_string($parentidentifier) . "'",
                'measuredate' => "'" . pg_escape_string($measuredate) . "'",
                'height' => trim($data[3]),
                'stdev' => trim($data[4]),
                'hfm' => trim($data[5]),
                'cyclenumber' => trim($data[6]) ? trim($data[6]) : '0',
                'flag' => trim($data[7]) ? trim($data[7]) : '0'
            );
            
            /*
             * Insert measures (removing old ones !)
             */
            if (pg_query($this->dbh,'INSERT INTO ' . $this->Controller->getDbConnector()->getSchema() . '.' . 'measures (' . join(',', array_keys($fieldsValues)) . ') VALUES (' . join(',', array_values($fieldsValues)) . ')')) {
                $count++;
            }
            
        }

        /*
         * Set up remaining products fields
         */
        $productsValue['comments'] = '\'' . pg_escape_string($comments) . '\'';
        $productsValue['startdate'] = '\'' . pg_escape_string($this->decimalYearToDate($startdate)) . '\'';
        $productsValue['completiondate'] = '\'' . pg_escape_string($this->decimalYearToDate($completiondate)) . '\'';
        
        /*
         * This is a break of the REST model since POST should be create only 
         * and PUT is update only...but operationaly it's easier to do like this
         * 
         * Data already exist => update
         */
        if ($this->resourceExists($parentidentifier)) {
            
            unset($productsValue['identifier']);
            $update = array();
            foreach ($productsValue as $key => $value) {
                array_push($update, $key . '=' . $value);
            }
            $query = pg_query($this->dbh, 'UPDATE ' . $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' SET ' . join(',', $update) . ' WHERE identifier=\'' . $parentidentifier . '\'');
        }
        else {
            $query = pg_query($this->dbh, 'INSERT INTO ' . $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' (' . join(',', array_keys($productsValue)) . ') VALUES (' . join(',', array_values($productsValue)) . ')');
        }
        if (!$query) {
            pg_query($this->dbh, 'ROLLBACK');
            throw new Exception('Cannot insert ' . $parentidentifier, 500);
        }
        else {
            pg_query($this->dbh, 'COMMIT');
        }
        
        return array('measures' => $count);
 
    }

    /**
     * Ingest lake file
     * 
     * File example
     * 
     *  lake=balkhash;country=Kazakstan;basin=Balkhash;lat= 45.73;lon= 76.42;date=2012/12/12;first_date= 1992.738;last_date= 2011.861;type=operationnal;diff=public
     *  
     *  # data file format
     *  # (1): decimal year, (2): date = yyyy/mm/dd, (3): time = hh:mm
     *  # (4): heigth above surface of ref (m), (5): standard deviation from heigth (m)
     *  # (6): area (km2), (7): volume with respect to volume of first date (km3)
     *  # (8): flag
     *  1992.738 ; 1992/09/26 ; 02:35 ;  341.28 ;    0.01 ;  ;  ; 
     *  ...etc...
     * 
     * @param array $file - Above file split within an array by $this->create function (one line per array entry)
     */
    private function ingestLakeResource($file) {
        
        /*
         * Initialize key/value for products table
         */
        $productsValue = array(
            'hydretype' => "'L'",
            'publisheddate' => 'now()'
        );
        
        /*
         * Initialize info from header
         * 
         *  lake=balkhash;country=Kazakstan;basin=Balkhash;lat= 45.73;lon= 76.42;date=2012/12/12;first_date= 1992.738;last_date= 2011.861;type=operationnal;diff=public
         */
        $infos = explode(';', rtrim($file[0]));
        foreach ($infos as $info) {

            $kvp = explode('=', $info);
            $kvp[0] = trim($kvp[0]);
            $kvp[1] = trim($kvp[1]);

            /*
             * Identifier is lake name prefixed by L_
             */
            if ($kvp[0] === 'lake') {
                $lake = $kvp[1];
                $parentidentifier = 'L_' . $lake;
                $productsValue['identifier'] = '\'' . $parentidentifier . '\'';
            }
            else if ($kvp[0] === 'lat') {
                $latitude = floatval($kvp[1]);
            }
            else if ($kvp[0] === 'lon') {
                $longitude = floatval($kvp[1]);
            }
            else if ($kvp[0] === 'basin') {
                $basin = $kvp[1];
            }
            else if ($kvp[0] === 'country') {
                $productsValue['country'] = '\'' . pg_escape_string($kvp[1]) . '\'';
            }
            else if ($kvp[0] === 'date') {
                $productsValue['modifieddate'] = '\'' . pg_escape_string($this->decimalYearToDate($kvp[1])) . '\'';
            }
            else if ($kvp[0] === 'type') {
                $status = $kvp[1];
                $productsValue['status'] = '\'' . pg_escape_string($status) . '\'';
            }
            else if ($kvp[0] === 'diff') {
                $productsValue['diffusion'] = '\'' . pg_escape_string($kvp[1]) . '\'';
            }
            
            $productsValue['basin'] = '\'' . pg_escape_string($basin) . '\'';
            $productsValue['lake'] = '\'' . pg_escape_string($lake) . '\'';
            $productsValue['producttype'] = '\'WATERHEIGHT\'';
            $productsValue['processinglevel'] = '\'LEVEL3B\'';
            
            /*
             * Keywords :
             *  add basin and lake (read within header)
             *  add country and continent (call iTag)
             */
            $radius = 0.01;
            $lonmin = $longitude - $radius;
            $latmin = $latitude - $radius;
            $lonmax = $longitude + $radius;
            $latmax = $latitude + $radius;
            $wkt = 'POLYGON((' . $lonmin . ' ' . $latmin . ',' . $lonmin . ' ' . $latmax . ',' . $lonmax . ' ' . $latmax . ',' . $lonmax . ' ' . $latmin . ',' . $lonmin . ' ' . $latmin . '))';
            $keywords = $this->getTags($wkt, 'countries=true&continents=true');
            if ($basin) {
                array_push($keywords, $this->quoteForHstore('basin:' . strtolower($basin)) . '=> NULL');
            }
            if ($lake) {
                array_push($keywords, $this->quoteForHstore('lake:' . strtolower($lake)) . '=> NULL');
            }
            $productsValue['keywords'] = '\'' . join(',', $keywords) . '\'';
   
            /*
             * Geometry
             */
            $productsValue['geom'] = 'ST_GeomFromText(\'POINT(' . $longitude . ' ' . $latitude . ')\', 4326)';
            
        }

        /*
         * Prepare statement
         */
        pg_query($this->dbh, 'BEGIN');
        
        /*
         * Remove previous measures attached to $identifier
         */
        pg_query($this->dbh,'DELETE FROM ' . $this->Controller->getDbConnector()->getSchema() . '.' . 'measures WHERE parentidentifier=\'' . pg_escape_string($parentidentifier) . '\'');
        
        /*
         * Retrieve measures
         */
        $startdate = 99999;
        $completiondate = -99999;
        $comments = '';
        $count = 0;
        for ($i = 1, $l = count($file); $i < $l; $i++) {

            $line = $file[$i];

            /*
             * Comments start with '#'
             */
            if (substr(trim($line), 0, 1) === '#') {
                $comments .= $line;
                continue;
            }
            /*
             * Skip empty lines
             */
            else if (trim($line) === '') {
                continue;
            }

            /*
             * Data
             * 
             *  # data file format
             *  # (1): decimal year, (2): date = yyyy/mm/dd, (3): time = hh:mm
             *  # (4): heigth above surface of ref (m), (5): standard deviation from heigth (m)
             *  # (6): area (km2), (7): volume with respect to volume of first date (km3)
             *  # (8): flag
             *  1992.738 ; 1992/09/26 ; 02:35 ;  341.28 ;    0.01 ;  ;  ; 
             *  ...etc...
             */
            $data = explode(';', rtrim($line));
            $measuredate = floatval(trim($data[0]));
            $startdate = min($startdate, $measuredate);
            $completiondate = max($completiondate, $measuredate);
            
            /*
             * Table measures
             * 
             * See hydreDB.sql for fields description
             */
            $fieldsValues = array(
                'identifier' => "'" . pg_escape_string($parentidentifier . ':' . $measuredate) . "'",
                'parentidentifier' => "'" . pg_escape_string($parentidentifier) . "'",
                'measuredate' => "'" . pg_escape_string($measuredate) . "'",
                'height' => trim($data[3]),
                'stdev' => trim($data[4]),
                'area' => trim($data[5]) ? trim($data[5]) : '0',
                'cyclenumber' => trim($data[6]) ? trim($data[6]) : '0',
                'flag' => trim($data[7]) ? trim($data[7]) : '0',
            );
            
            /*
             * Insert measures
             */
            if (pg_query($this->dbh,'INSERT INTO ' . $this->Controller->getDbConnector()->getSchema() . '.' . 'measures (' . join(',', array_keys($fieldsValues)) . ') VALUES (' . join(',', array_values($fieldsValues)) . ')')) {
                $count++;
            }
            
        }

        /*
         * Set up remaining products fields
         */
        $productsValue['comments'] = '\'' . pg_escape_string($comments) . '\'';
        $productsValue['startdate'] = '\'' . pg_escape_string($this->decimalYearToDate($startdate)) . '\'';
        $productsValue['completiondate'] = '\'' . pg_escape_string($this->decimalYearToDate($completiondate)) . '\'';
        
        /*
         * This is a break of the REST model since POST should be create only 
         * and PUT is update only...but operationaly it's easier to do like this
         * 
         * Data already exist => update
         */
        if ($this->resourceExists($parentidentifier)) {
            
            unset($productsValue['identifier']);
            $update = array();
            foreach ($productsValue as $key => $value) {
                array_push($update, $key . '=' . $value);
            }
            $query = pg_query($this->dbh, 'UPDATE ' . $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' SET ' . join(',', $update) . ' WHERE identifier=\'' . $parentidentifier . '\'');
            
        }
        else {
            $query = pg_query($this->dbh, 'INSERT INTO ' . $this->Controller->getDbConnector()->getSchema() . '.' . $this->Controller->getDbConnector()->getTable() . ' (' . join(',', array_keys($productsValue)) . ') VALUES (' . join(',', array_values($productsValue)) . ')');
        }
        if (!$query) {
            pg_query($this->dbh, 'ROLLBACK');
            throw new Exception('Cannot insert ' . $parentidentifier, 500);
        }
        else {
            pg_query($this->dbh, 'COMMIT');
        }
        
        return array('measures' => $count);
        
    }

    /**
     * Return satellite name from $code
     * 
     * @param string $code
     */
    private function getSatelliteName($code) {

        if (!$code) {
            return null;
        }
        $code = strtolower($code);
        $satellites = array(
            'tpx' => 'TOPEX/POSEIDON',
            'env' => 'ENVISAT',
            'ja2' => 'JASON2',
            'atk' => 'SARAL/ALTIKA',
            'er1' => 'ERS1',
            'er2' => 'ERS2'
        );

        return isset($satellites[$code]) ? $satellites[$code] : null;
    }

    /**
     * Return basin from $code
     * 
     * Nomenclature follows "Water Resources eAtlas" :
     * 
     *      C. Revenga, S. Murray, J. Abramovitz, and A. Hammond, 1998.
     *      Watersheds of the World: Ecological Value and Vulnerability. Washington, DC: World Resources Institute.
     * 
     *      U. S. Geological Survey’s Hydro 1k data available at: http://edcdaac.usgs.gov/gtopo30/hydro/
     * 
     * @param string $code
     */
    private function getBasinName($code) {

        if (!$code) {
            return null;
        }
        $code = strtoupper($code);
        $basins = array(
            'A01' => 'Lake Chad',
            'A02' => 'Congo',
            'A03' => 'Cuanza',
            'A04' => 'Cunene',
            'A05' => 'Jubba',
            'A06' => 'Limpopo',
            'A07' => 'Mangoky',
            'A08' => 'Mania',
            'A09' => 'Niger',
            'A10' => 'Nile',
            'A11' => 'Ogooue',
            'A12' => 'Okavango',
            'A13' => 'Orange',
            'A14' => 'Oued Draa',
            'A15' => 'Rufiji',
            'A16' => 'Senegal',
            'A17' => 'Shaballe',
            'A18' => 'Turkana',
            'A19' => 'Volta',
            'A20' => 'Zambezi',
            'E01' => 'Dalalven',
            'E02' => 'Danube',
            'E03' => 'Daugava',
            'E04' => 'Dnieper',
            'E05' => 'Dniester (Nistru)',
            'E06' => 'Don',
            'E07' => 'Duero',
            'E08' => 'Ebro',
            'E09' => 'Elbe',
            'E10' => 'Garonne',
            'E11' => 'Glomma-Laagen',
            'E12' => 'Guadalquivir',
            'E13' => 'Kemijoki',
            'E14' => 'Kizilirmak',
            'E15' => 'Kura-Araks',
            'E16' => 'Lake Ladoga',
            'E17' => 'Loire',
            'E18' => 'North Dvina',
            'E19' => 'Oder',
            'E20' => 'Po',
            'E21' => 'Rhine & Maas',
            'E22' => 'Rhone',
            'E23' => 'Seine',
            'E24' => 'Tagus',
            'E25' => 'Tigris & Euphrates',
            'E26' => 'Ural',
            'E27' => 'Vistula',
            'E28' => 'Volga',
            'E29' => 'Weser',
            'AS01' => 'Amu Darya',
            'AS02' => 'Amur',
            'AS03' => 'Lake Balkhash',
            'AS04' => 'Brahmaputra',
            'AS05' => 'Chao Phraya',
            'AS06' => 'Ganges',
            'AS07' => 'Godavari',
            'AS08' => 'Hong (Red River)',
            'AS09' => 'Huang He (Yellow River)',
            'AS10' => 'Indigirka',
            'AS11' => 'Indus',
            'AS12' => 'Irrawaddy',
            'AS13' => 'Kapuas',
            'AS14' => 'Kolyma',
            'AS15' => 'Krishna',
            'AS16' => 'Lena',
            'AS17' => 'Mahakam',
            'AS18' => 'Mahanadi',
            'AS19' => 'Mekong',
            'AS20' => 'Narmada',
            'AS21' => 'Ob',
            'AS22' => 'Pechora',
            'AS23' => 'Salween',
            'AS24' => 'Syr Darya',
            'AS25' => 'Tapti',
            'AS26' => 'Tarim',
            'AS27' => 'Xun Jiang',
            'AS28' => 'Yalu Jiang',
            'AS29' => 'Yangtze',
            'AS30' => 'Yenisey',
            'NA01' => 'Alabama & Tombigbee',
            'NA02' => 'Balsas',
            'NA03' => 'Brazos',
            'NA04' => 'Colorado',
            'NA05' => 'Columbia',
            'NA06' => 'Fraser',
            'NA07' => 'Hudson',
            'NA08' => 'Mackenzie',
            'NA09' => 'Mississippi',
            'NA10' => 'Nelson',
            'NA11' => 'Rio Grande',
            'NA12' => 'Rio Grande de Santiago',
            'NA13' => 'Sacramento',
            'NA14' => 'Saint Lawrence',
            'NA15' => 'San Pedro & Usumacinta',
            'NA16' => 'Susquehanna',
            'NA17' => 'Thelon',
            'NA18' => 'Yaqui',
            'NA19' => 'Yukon',
            'SA01' => 'Amazon',
            'SA02' => 'Chubut',
            'SA03' => 'Magdalena',
            'SA04' => 'Orinoco',
            'SA05' => 'Parana',
            'SA06' => 'Parnaiba',
            'SA07' => 'Rio Colorado',
            'SA08' => 'Sao Francisco',
            'SA09' => 'Lake Titicaca & Salar de Uyuni',
            'SA10' => 'Tocantins',
            'SA11' => 'Uruguay',
            'OC01' => 'Burdekin-Belyando',
            'OC02' => 'Dawson',
            'OC03' => 'Fly',
            'OC04' => 'Murray-Darling',
            'OC05' => 'Sepik'
        );

        return isset($basins[$code]) ? $basins[$code] : null;
    }

    /**
     * Return basin or river name from $code
     * 
     * @param string $code
     */
    private function getRiverOrBasinName($code) {

        if (!$code) {
            return null;
        }
        $code = strtolower($code);
        $basinsAndRivers = array(
            'amz' => 'Amazon',
            'mad' => 'Madera',
            'neg' => 'Rio Negro',
            'tap' => 'Tapajos',
            'con' => 'Congo',
            'mrg' => 'Marenga',
            'kas' => 'Kasai',
            'oub' => 'Oubangui',
            'uel' => 'Uele',
            'dan' => 'Danube',
            'dni' => 'Dniepr',
            'gan' => 'Ganges',
            'gha' => 'Ghaghara',
            'bra' => 'Brahmaputra',
            'mis' => 'Mississipi',
            'nig' => 'Niger',
            'ben' => 'Benoue',
            'vol' => 'Volta',
            'nil' => 'Nile',
            'ore' => 'Orinoco',
            'gua' => 'Guaviare',
            'met' => 'Meta',
            'par' => 'Parana',
            'yan' => 'Yangtse',
            'yel' => 'Yellow River',
            'wei' => 'Weihe',
            'zam' => 'Zambeze'
        );

        return isset($basinsAndRivers[$code]) ? $basinsAndRivers[$code] : null;
    }

    /**
     * Add 1 to month and day
     * 
     * @param string $date (format YYYY/MM/DD with MM and DD starting at 0)
     */
    private function correctDate($date) {
        $splitted = explode('/', trim($date));
        $year = $splitted[0];
        $month = intval($splitted[1]) + 1;
        $day = intval($splitted[2]) + 1;
        if ($month < 10) {
            $month = '0' . $month;
        }
        if ($day < 10) {
            $day = '0' . $day;
        }
        return $year . '-' . $month . '-' . $day;
    }

    /**
     * Convert a decimal year to ISO8601 timestamp
     * 
     * @param float $dyear
     * @return string
     */
    private function decimalYearToDate($dyear) {

        date_default_timezone_set('UTC');

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