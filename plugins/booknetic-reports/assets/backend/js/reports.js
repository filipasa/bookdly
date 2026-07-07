(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        let chart_1;
        let chart_2;
        let colors = [
            '#FFD17A',
            '#DBC9FF',
            '#85CEF9',
            '#9FFFB2',
            'rgb(242, 210, 73)',
            'rgb(147, 185, 198)',
            'rgb(204, 197, 168)',
            'rgb(83, 186, 205)',
            'rgb(156, 196, 45)',
            'rgb(152, 170, 252)',
            'rgb(97, 166, 86)',
            'rgb(225, 124, 36)',
            'rgb(210, 32, 48)'
        ];

        function formatDateTime( value )
        {
            let options = {};
            const locale = $('#reports-locale').val().replace('_', '-');

            const type = $( '[data-appointment-report-via-price-type].selected-item' ).data( 'appointment-report-via-price-type' );

            if ( type === 'monthly' )
            {
                options = {
                    month: 'long',
                    year: 'numeric',
                };
            }
            else if ( type === 'annualy' )
            {
                options = {
                    year: 'numeric',
                };
            }

            return new Intl.DateTimeFormat( locale, options ).format( new Date( value ) );
        }

        $(document).on('click', '[data-appointment-report-via-count-type]', function()
        {
            const type = $(this).attr('data-appointment-report-via-count-type');

            $(this).parent().prev().children('span').text( $(this).text() );

            let filters = {};
            $(this).closest('.fs_portlet').find('select[data-filter]').each(function()
            {
                let filter_name = $(this).data('filter'),
                    filter_val  = $(this).val();

                filters[ filter_name ] = filter_val;
            });

            $(this).parent().children('.selected-item').removeClass('selected-item');
            $(this).addClass('selected-item');

            booknetic.ajax('Reports.get_appointment_report_via_count', {type, filters}, function(response)
            {
                if ( chart_1 )
                {
                    chart_1.destroy();
                }

                let bgColors = [];

                for ( let i = 0; i <= response.response.values.length; i++ )
                {
                    bgColors.push( colors[ i % colors.length ] );
                }

                var ctx = document.getElementById('appointment-count').getContext('2d');
                chart_1 = new Chart( ctx, {
                    type: 'bar',
                    data: {
                        labels: response.response.labels,
                        datasets: [ {
                            data: response.response.values,
                            backgroundColor: bgColors,
                            borderColor: bgColors,
                            borderWidth: 1
                        } ]
                    },
                    options: {
                        legend: {
                            display: false
                        },
                        scales: {
                            xAxes: [ {
                                ticks: {
                                    callback: formatDateTime
                                },
                            } ],
                            yAxes: [ {
                                ticks: {
                                    beginAtZero: true
                                }
                            } ]
                        },
                        gridLines: {
                            color: '#E3EAF3'
                        }
                    }
                } );

            });

        }).on('click', '[data-appointment-report-via-price-type]', function()
        {
            const type = $(this).attr('data-appointment-report-via-price-type');

            $(this).parent().prev().children('span').text( $(this).text() );

            let filters = {};
            $(this).closest('.fs_portlet').find('select[data-filter]').each(function()
            {
                let filter_name = $(this).data('filter'),
                    filter_val  = $(this).val();

                filters[ filter_name ] = filter_val;
            });

            $(this).parent().children('.selected-item').removeClass('selected-item');
            $(this).addClass('selected-item');

            booknetic.ajax('Reports.get_appointment_report_via_price', {type, filters}, function(response)
            {
                if ( chart_2 )
                {
                    chart_2.destroy();
                }

                let ctx = document.getElementById( 'appointment-price' ).getContext( '2d' );
                chart_2 = new Chart( ctx, {
                    type: 'line',
                    data: {
                        labels: response.response.labels,
                        datasets: [ {
                            data: response.response.values,
                            fill: true,
                            borderColor: "#5DD775",
                            borderWidth: 2,
                            backgroundColor: "#eefbf1",
                            pointBackgroundColor: '#5DD775',
                            pointRadius: 4,
                            pointHoverBackgroundColor: "#eefbf1",
                            pointHoverRadius: 4,
                            pointHoverBorderWidth: 2,
                            pointHoverBorderColor: '#5DD775'
                        } ]
                    },
                    options: {
                        legend: {
                            display: false
                        },
                        scales: {
                            xAxes: [ {
                                ticks: {
                                    callback: formatDateTime
                                },
                            } ],
                            yAxes: [ {
                                ticks: {
                                    beginAtZero: true
                                },
                                gridLines: {
                                    color: '#E3EAF3'
                                }
                            } ]
                        }
                    }
                } );

            });
        }).on('click', '[data-report-by-location-type]', function()
        {
            const type = $(this).attr('data-report-by-location-type');

            $(this).parent().prev().children('span').text( $(this).text() );

            booknetic.ajax('Reports.get_location_report', {'type': type}, function(response)
            {
                let bgColors = [];

                for ( let i = 0; i <= response.response.values.length; i++ )
                {
                    bgColors.push( colors[ i % colors.length ] );
                }

                let ctx = document.getElementById( 'location-report' ).getContext( '2d' );

                new Chart( ctx, {
                    type: 'doughnut',
                    data: {
                        datasets: [ {
                            backgroundColor: bgColors,
                            data: response.response.values
                        } ],

                        labels: response.response.labels
                    },
                    options: {
                        legend: {
                            position: 'right',

                            labels: {
                                usePointStyle: true,
                                generateLabels: function (chart)
                                {
                                    var data = chart.data;
                                    if ( data.labels.length && data.datasets.length )
                                    {
                                        return data.labels.map( function (label, i) {
                                            var meta = chart.getDatasetMeta( 0 );
                                            var ds = data.datasets[ 0 ];
                                            var arc = meta.data[ i ];
                                            var custom = arc && arc.custom || {};
                                            var getValueAtIndexOrDefault = Chart.helpers.getValueAtIndexOrDefault;
                                            var arcOpts = chart.options.elements.arc;
                                            var fill = custom.backgroundColor ? custom.backgroundColor : getValueAtIndexOrDefault( ds.backgroundColor, i, arcOpts.backgroundColor );
                                            var stroke = custom.borderColor ? custom.borderColor : getValueAtIndexOrDefault( ds.borderColor, i, arcOpts.borderColor );
                                            var bw = custom.borderWidth ? custom.borderWidth : getValueAtIndexOrDefault( ds.borderWidth, i, arcOpts.borderWidth );

                                            var value = chart.config.data.datasets[ arc._datasetIndex ].data[ arc._index ];

                                            return {
                                                // Instead of `text: label,`
                                                // We add the value to the string
                                                text: label + " : " + value,
                                                fillStyle: fill,
                                                strokeStyle: stroke,
                                                lineWidth: bw,
                                                hidden: isNaN( ds.data[ i ] ) || meta.data[ i ].hidden,
                                                index: i
                                            };
                                        } );
                                    }
                                    else
                                    {
                                        return [];
                                    }
                                }
                            }


                        },
                        responsive: true
                    }
                } );

            });
        }).on('click', '[data-report-by-staff-type]', function()
        {
            const type = $(this).attr('data-report-by-staff-type');

            $(this).parent().prev().children('span').text( $(this).text() );

            booknetic.ajax('Reports.get_staff_report', {'type': type}, function(response)
            {
                let bgColors = [];

                for ( let i = 0; i <= response.response.values.length; i++ )
                {
                    bgColors.push( colors[ i % colors.length ] );
                }

                let ctx = document.getElementById( 'staff-report' ).getContext( '2d' );

                new Chart( ctx, {
                    type: 'doughnut',
                    data: {
                        datasets: [ {
                            backgroundColor: bgColors,
                            data: response.response.values
                        } ],

                        labels: response.response.labels
                    },
                    options: {
                        legend: {
                            position: 'right',

                            labels: {
                                usePointStyle: true,
                                generateLabels: function (chart)
                                {
                                    var data = chart.data;
                                    if ( data.labels.length && data.datasets.length )
                                    {
                                        return data.labels.map( function (label, i) {
                                            var meta = chart.getDatasetMeta( 0 );
                                            var ds = data.datasets[ 0 ];
                                            var arc = meta.data[ i ];
                                            var custom = arc && arc.custom || {};
                                            var getValueAtIndexOrDefault = Chart.helpers.getValueAtIndexOrDefault;
                                            var arcOpts = chart.options.elements.arc;
                                            var fill = custom.backgroundColor ? custom.backgroundColor : getValueAtIndexOrDefault( ds.backgroundColor, i, arcOpts.backgroundColor );
                                            var stroke = custom.borderColor ? custom.borderColor : getValueAtIndexOrDefault( ds.borderColor, i, arcOpts.borderColor );
                                            var bw = custom.borderWidth ? custom.borderWidth : getValueAtIndexOrDefault( ds.borderWidth, i, arcOpts.borderWidth );

                                            var value = chart.config.data.datasets[ arc._datasetIndex ].data[ arc._index ];

                                            return {
                                                // Instead of `text: label,`
                                                // We add the value to the string
                                                text: label + " : " + value,
                                                fillStyle: fill,
                                                strokeStyle: stroke,
                                                lineWidth: bw,
                                                hidden: isNaN( ds.data[ i ] ) || meta.data[ i ].hidden,
                                                index: i
                                            };
                                        } );
                                    }
                                    else
                                    {
                                        return [];
                                    }
                                }
                            }


                        },
                        responsive: true
                    }
                } );
            });
        });

        $(document).find('[data-appointment-report-via-count-type]:eq(0)').click();
        $(document).find('[data-appointment-report-via-price-type]:eq(0)').click();
        $(document).find('[data-report-by-location-type]:eq(0)').click();
        $(document).find('[data-report-by-staff-type]:eq(0)').click();

        $('.fs_portlet_content select[data-filter]').select2({
            theme: 'bootstrap',
            allowClear: true
        }).on('change', function ()
        {
            $(this).closest('.fs_portlet').find('.dropdown-item.selected-item').click();
        });

    });

})(jQuery);
