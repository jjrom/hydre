/*
 * RESTo
 * 
 * RESTo - REstful Semantic search Tool for geOspatial 
 * 
 * Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
 * 
 * jerome[dot]gasperi[at]gmail[dot]com
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
(function(window) {

    window.R = window.R || {};

    /**
     * Update result entries after a search
     * 
     * @param {array} json
     * @param {boolean} updateMapshup - true to update mapshup
     * 
     */
    window.R.updateResultEntries = function(json, updateMapshup) {

        var i, l, j, k, feature, key, keyword, keywords, type, $content, $actions, value, title, addClass, platform, results, resolution, self = this;

        json = json || {};

        /*
         * Update pagination
         */
        var first = '', previous = '', next = '', last = '', pagination = '', selfUrl = '#';

        if (json.missing) {
            pagination = '';
        }
        else if (json.totalResults === 0) {
            pagination = self.translate('_noResult');
        }
        else {

            if ($.isArray(json.links)) {
                for (i = 0, l = json.links.length; i < l; i++) {
                    if (json.links[i]['rel'] === 'first') {
                        first = ' <a class="resto-ajaxified" href="' + self.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + self.translate('_firstPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'previous') {
                        previous = ' <a class="resto-ajaxified" href="' + self.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + self.translate('_previousPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'next') {
                        next = ' <a class="resto-ajaxified" href="' + self.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + self.translate('_nextPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'last') {
                        last = ' <a class="resto-ajaxified" href="' + self.updateURL(json.links[i]['href'], {format: 'html'}) + '">' + self.translate('_lastPage') + '</a> ';
                    }
                    if (json.links[i]['rel'] === 'self') {
                        selfUrl = json.links[i]['href'];
                    }
                }
            }

            if (json.totalResults === 1) {
                pagination += self.translate('_oneResult', [json.totalResults]);
            }
            else if (json.totalResults > 1) {
                pagination += self.translate('_multipleResult', [json.totalResults]);
            }

            pagination += json.startIndex ? '&nbsp;|&nbsp;' + first + previous + self.translate('_pagination', [json.startIndex, json.lastIndex]) + next + last : '';

        }

        /*
         * Update each pagination element
         */
        $('.resto-pagination').each(function() {
            $(this).html(pagination);
        });

        /*
         * Iterate on features and update result container
         */
        $content = $('.resto-content').empty();
        for (i = 0, l = json.features.length; i < l; i++) {

            feature = json.features[i];
            
            /*
             * Display structure
             *  
             *  <div class="resto-entry" id="">
             *      <div class="padded-bottom">
             *         Platform / startDate
             *      </div>
             *      <div class="resto-actions">
             *          ...
             *      </div>
             *      <div class="resto-keywords">
             *          ...
             *      </div> 
             *  </div>
             * 
             */

            /*
             * Satellite
             */
            platform = feature.properties['platform'];
            if (feature.properties.keywords && feature.properties.keywords[feature.properties['platform']]) {
                platform = '<a href="' + self.updateURL(feature.properties.keywords[feature.properties['platform']]['href'], {format: 'html'}) + '" class="resto-ajaxified resto-updatebbox resto-keyword resto-keyword-platform" title="' + self.translate('_thisResourceWasAcquiredBy', [feature.properties['platform']]) + '">' + feature.properties['platform'] + '</a> ';
            }
            $content.append('<li><div class="resto-entry" id="rid' + i + '" fid="' + feature.id + '"><div class="padded-bottom"><span class="platform">' + platform + (platform && feature.properties['instrument'] ? "/" + feature.properties['instrument'] : "") + '</span> | <span class="timestamp">' + feature.properties['startDate'] + '</span></div><div class="resto-actions"></div><div class="resto-keywords"></div></div></li>');
            $actions = $('.resto-actions', $('#rid' + i));
            
            /*
             * Zoom on feature
             */
            $actions.append('<a class="fa fa-bullseye centerOnFeature" href="#" title="' + self.translate('_centerOnFeature') + ' "></a>');
            
            /*
             * Metadata
             */
            if ($.isArray(feature.properties['links'])) {
                for (j = 0, k = feature.properties['links'].length; j < k; j++) {
                    if (feature.properties['links'][j]['type'] === 'text/html') {
                        $actions.append('<a class="fa fa-file-o" href="' + feature.properties['links'][j]['href'] + '" title="' + self.translate('_viewMetadata') + ' "></a>');
                    }
                }
            }

            /*
             * Services
             */
            if (feature.properties['services']) {

                /*
                 * Download
                 */
                if (feature.properties['services']['download'] && feature.properties['services']['download']['url']) {
                    $actions.append('<a class="fa fa-cloud-download" href="' + feature.properties['services']['download']['url'] + '"' + (feature.properties['services']['download']['mimeType'] === 'text/html' ? ' target="_blank"' : '') + ' title="' + self.translate('_download') + '"></a>');
                }

                /*
                 * View
                 */
                if (feature.properties['services']['browse'] && feature.properties['services']['browse']['layer']) {
                    if (window.M) {
                        message = {
                            title: feature.id,
                            type: feature.properties['services']['browse']['layer']['type'],
                            layers: feature.properties['services']['browse']['layer']['layers'],
                            url: feature.properties['services']['browse']['layer']['url'].replace('%5C', ''),
                            zoomOnNew:'always'
                        };
                        $actions.append('<a class="fa fa-eye resto-addLayer" data="' + encodeURI(JSON.stringify(message)) + '" href="#" title="' + self.translate('_viewMapshupFullResolution') + '"></a>');
                    }
                }
            }
            
            /*
             * Center on feature
             */
            (function($div) {
                $('.centerOnFeature', $div).click(function(e) {
                    e.preventDefault();
                    var f = window.M.Map.Util.getFeature(window.M.Map.Util.getLayerByMID('__resto__'), $div.attr('fid'));
                    if (f) {
                        window.M.Map.zoomTo(f.geometry.getBounds(), false);
                        window.M.Map.featureInfo.hilite(f);
                        $('.resto-entry').each(function(){
                            $(this).removeClass('selected');
                        });
                        $div.addClass('selected');
                    }
                });    
            })($('#rid' + i));
            
            /*
             * Keywords are splitted in different types 
             * 
             *  - type = landuse (forest, water, etc.)
             *  - type = country/continent/city
             *  - type = platform/instrument
             *  - type = date
             *  - type = null and keyword start with a '#' = tags
             *  
             */
            if (feature.properties.keywords) {
                results = [];
                keywords = {
                    landuse: {
                        title: '_landUse',
                        keywords: []
                    },
                    location: {
                        title: '_location',
                        keywords: []
                    },
                    tag: {
                        title: '_tags',
                        keywords: []
                    },
                    resolution: {
                        title: '_resolution',
                        keywords: []
                    },
                    other: {
                        title: '_other',
                        keywords: []
                    }
                };
                for (key in feature.properties.keywords) {

                    keyword = feature.properties.keywords[key];
                    value = key;
                    title = "";
                    addClass = null;
                    if (keyword.type === 'landuse') {
                        type = 'landuse';
                        value = value + ' (' + Math.round(keyword.value) + '%)';
                        addClass = keyword.id;
                        title = self.translate('_thisResourceContainsLanduse', [keyword.value, key]);
                    }
                    else if (keyword.type === 'country' || keyword.type === 'continent') {
                        type = 'location';
                        title = self.translate('_thisResourceIsLocated', [key]);
                    }
                    else if (keyword.type === 'city') {
                        type = 'location';
                        title = self.translate('_thisResourceContainsCity', [key]);
                    }
                    else if (keyword.type === 'platform' || keyword.type === 'instrument') {
                        continue;
                    }
                    else if (keyword.type === 'date') {
                        continue;
                    }
                    else if (key.indexOf("#") === 0) {
                        type = 'tag';
                    }
                    else {
                        type = 'other';
                    }
                    keywords[type]['keywords'].push('<a href="' + self.updateURL(feature.properties.keywords[key]['href'], {format: 'html'}) + '" class="resto-ajaxified resto-updatebbox resto-keyword' + (feature.properties.keywords[key]['type'] ? ' resto-keyword-' + feature.properties.keywords[key]['type'].replace(' ', '') : '') + (addClass ? ' resto-keyword-' + addClass : '') + '" title="' + title + '">' + value + '</a> ');
                }

                /*
                 * Resolution
                 */
                if (feature.properties['resolution']) {
                    resolution = self.getResolution(feature.properties['resolution']);
                    keywords['resolution']['keywords'].push(feature.properties['resolution'] + 'm - <a href="' + self.updateURL(selfUrl, {q: self.translate(resolution), format: 'html'}) + '" class="resto-ajaxified resto-updatebbox resto-keyword resto-keyword-resolution" title="' + self.translate(resolution) + '">' + resolution + '</a>');
                }

                for (key in keywords) {
                    if (keywords[key]['keywords'].length > 0) {
                        results.push('<td class="title">' + self.translate(keywords[key]['title']) + '</td><td>&nbsp;</td><td class="values">' + keywords[key]['keywords'].join(', ') + '</td>');
                    }
                }

                $('.resto-keywords', $('#rid' + i)).html('<table>' + results.join('</tr>') + '</table>');
            }

        }
        
    };

})(window);
