(function(c) {

    /*
     * Update configuration options
     * 
     * Should be changed to match target server
     */
    c["general"].rootUrl = 'http://localhost/hydre/mapshup';
    c["general"].serverRootUrl = c["general"].rootUrl + "/s";
    c["general"].proxyUrl = null;
    c["general"].confirmDeletion = false;
    c["upload"].allowedMaxSize = 1000000000;

    /*
     * !! DO NOT EDIT UNDER THIS LINE !!
     */
    c["general"].themePath = "/js/mapshup/theme/default";
    c["general"].displayContextualMenu = true;
    c["general"].displayCoordinates = true;
    c["general"].displayScale = false;
    c["general"].timeLine = {
        enable: false
    };
    c["general"].overviewMap = "closed";
    c.remove("layers", "Streets");
    //c.remove("layers", "Satellite");
    c.remove("layers", "Relief");
    c.remove("layers", "MapQuest OSM");
    c.remove("layers", "OpenStreetMap");
    /*c.add("layers", {
        type: "Bing",
        title: "Satellite",
        key: "AmraZAAcRFVn6Vbxk_TVhhVZNt66x4_4SV_EvlfzvRC9qZ_2y6k1aNsuuoYS0UYy",
        bingType: "Aerial"
    });*/
    
    
    c.add("plugins",
    {
        name:"AddLayer",
        options:{
            allowedMaxSize:100000000,
            allowedLayerTypes:[
            {
                name:"KML"
            }
            ]
        }
    });

    
    c.extend("Navigation", {
        position: 'nw',
        orientation: 'h'
    });
    c.remove("plugins", "LayersManager");
    /*c.extend("LayersManager", {
        slideOverMap: false
    });*/
    c["general"].location = {
        lon: 0,
        lat: 40,
        zoom: 3
    };

    /*
     * Orbit
     */
    /*
    c.add("layers", {
        type: 'KML',
        url: 'http://localhost/devel/Visu_EN_Tracks_HiRes_GE_V2.kml',
        layers: 'orbit',
        srs: 'EPSG:4326'
    });
    */
    /*
    /*
     * Rivers
     */
    
    c.add("layers", {
        type: 'WMS',
        url: 'http://localhost/cgi-bin/mapserv?map=/Users/jrom/Devel/hydre/mapserver/hydroweb.map&',
        layers: 'rivers',
        srs: 'EPSG:3857'
    });
    /*
     * Basins
     */
   
    c.add("layers", {
        type: 'WMS',
        url: 'http://localhost/cgi-bin/mapserv?map=/Users/jrom/Devel/hydre/mapserver/hydroweb.map&',
        layers: 'basins',
        srs: 'EPSG:3857'
    });
   
    /*
    c.add("layers", {
        type: 'GeoJSON',
        url: 'http://localhost/devel/hydroweb/mapserver/data/basins/basins.json',
        clusterized: true,
        featureInfo: {
            title: "$DRAINAGE$"
        }
    });
    */
   
    /*
     * Basins through UTFGrids
     */
    c.add("layers", {
        type: "UTFGrid",
        title: "basins",
        url: c["general"].serverRootUrl + "/plugins/utfgrids/serve.php?name=basins&zxy=${z}/${x}/${y}",
        z: [0, 5],
        bbox: {
            bounds: "-180,-90,180,90",
            srs: "EPSG:4326"
        },
        info: {
            title: "$DRAINAGE$"/*,
            keys: {
                'code_06': {
                    transform: function(v) {
                    }
                }
            }*/
        }
    });
    
    

})(window.M.Config);
