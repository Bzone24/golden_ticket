       $(document).ready(function () {

    const dateRangePickerSpan = $('.date-range-picker span');
    let picker;

    // Helper to get query param by name
    function getQueryParam(param) {
        return new URLSearchParams(window.location.search).get(param);
    }

    $(function () {
        var start = moment();
        var end = moment();

        function cb(start, end, label) {
            const rangeLabels = ['Today', 'Yesterday', 'Last 7 Days', 'Last 30 Days', 'This Month', 'Last Month'];

            if (rangeLabels.includes(label)) {
                dateRangePickerSpan.html(label);
            } else {
                dateRangePickerSpan.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }

           if(label==='Today'){
     $('#date-range-picker-form input[name=start_date]').val(start.format('YYYY-MM-DD'));
     $('#date-range-picker-form input[name=end_date]').val('');
            $('#date-range-picker-form input[name=day]').val('');
           }else{
     $('#date-range-picker-form input[name=start_date]').val(start.format('YYYY-MM-DD'));

 $('#date-range-picker-form input[name=end_date]').val(end.format('YYYY-MM-DD'));
            $('#date-range-picker-form input[name=day]').val(rangeLabels.includes(label) ? label : '');
           }
       
           
        }

        picker = $('.date-range-picker').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        // Check query params on page load
        let qStart = getQueryParam('start_date');
        let qEnd = getQueryParam('end_date');
        let qDay = getQueryParam('day');

        if (qStart && qEnd) {
            let s = moment(qStart, 'YYYY-MM-DD');
            let e = moment(qEnd, 'YYYY-MM-DD');
            picker.data('daterangepicker').setStartDate(s);
            picker.data('daterangepicker').setEndDate(e);
            cb(s, e, qDay || '');
        } else {
            cb(start, end, 'Today');
        }

        // Reset button
        $('.reset-btn').on('click', function () {
            let today = moment();
            picker.data('daterangepicker').setStartDate(today);
            picker.data('daterangepicker').setEndDate(today);
            cb(today, today, 'Today');

            // Remove query parameters from URL without reloading
            const baseUrl = window.location.origin + window.location.pathname;
            const newUrl = `${baseUrl}?start_date=${moment().format('YYYY-MM-DD')}`;
            window.history.replaceState({}, '', newUrl);
            window.location.reload();
        });
    });
});
