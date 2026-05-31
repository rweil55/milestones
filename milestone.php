<?php
/*      Freewheeling Easy Mapping Application
 *		A collection of routines for display of trail maps and amenities
 *
 *      setting up and displaying milestones on the trail
 *
 * */
class freewheelingeasy_milestones
{

    public static function edit($attributes)
    {
        // set up milestone data
        global $errorBeg, $errorEnd, $eol;
        global $wpdbExtra;
        $msg = "";
        try {
            ini_set("display_errors", 1);
            $permission = rrwUtil::isAllowedToEditWithEmail($msg);
            if ($permission == false)
                return $msg;
            $editOne = rrwParam::String("id", $attributes);
            $actions = rrwParam::String("actions", $attributes);
            $tabMilestone = new rrwDisplayTable();
            $msg .= $tabMilestone->tablename("$wpdbExtra->milestones");
            $msg .= $tabMilestone->keyName("evId");
            $tabMilestone->sortDefault("evId");
            $tabMilestone->columnRead("Starting Milepost", "evMPstart", 10, 0);
            $msg .= $tabMilestone->columnRead("Ending Milepost", "evMPend", 10, 0);
            $msg .= $tabMilestone->columnRead("Milestone Date", "evDate", 20, 0);
            $msg .= $tabMilestone->columnRead("location in words - short", "description_location", 100, 0);
            $msg .= $tabMilestone->columnRead("Description - short", "description_short", 100, 0);
            $msg .= $tabMilestone->columnRead("Description - long", "description_long", 5000, 15);
            $sqlImportance = "select code, codeDescription from $wpdbExtra->codes where codeType='ev_importance' order by codeDescription";
            $msg .= $tabMilestone->dropdownOther("Importance", "evImportance", 20, $sqlImportance);
            $msg .= $tabMilestone->columnRead("Notes", "notes", 500, 15, "Not shown to public");
            $msg .= $tabMilestone->DoAction($actions);
        } catch (Exception $ex) {
            $msg .= "$errorBeg E#849 error " . $ex->getMessage() . " during milestoneSetup $errorEnd";
        }
        return $msg;
    } // end milestoneSetup

    public static function createMilestoneDisplay($attributes)
    {
        print("
<style>
.box-container {
    /* The core property to draw the line around the content */
    border: 2px solid black;
    padding: 5px;
    /*background-color: #f0f0f0; */
    border-radius: 8px; ded) */
    box-sizing: border-box;
    width: fit-content;
    assign:left;
}
</style>
");       // display milestone data
        global $errorBeg, $errorEnd, $eol;
        global $wpdbExtra, $rrw_milestones;
        $msg = "";
        try {
            $sqlCodes = "select code, codeDescription from $wpdbExtra->codes where codeType='ev_importance' order by code desc";
            $recCodes = $wpdbExtra->get_resultsA($sqlCodes);
            $msg .= "<div class=box-container> ";
            foreach ($recCodes as $rowCode) {
                $code = $rowCode['code'];
                $codeDescription = $rowCode['codeDescription'];
                $msg .= "<input type=checkbox class='importance-filter' checked value='$code'>$codeDescription,  ";
            }
            $msg .= "<input type='checkbox' id='Notes' >Notes";
            $msg .= " | sort by
            <select id=sortOrder>
                <option>date</option>
                <option>milepost</option>
                </select>
            </div>";
            $sqlMilestones = "select evDate, evMPstart, evMPend, description_location, description_short, notes, evImportance from $wpdbExtra->milestones order by evDate desc limit 500";
            $recMilestones = $wpdbExtra->get_resultsA($sqlMilestones);

            // Container where the milestones table will be rendered
            $msg .= "<div id=milestone-table-container></div>";

            $msg .= "
            <script>
            var milestones = " . json_encode($recMilestones) . ";
            console.log(milestones);
            console.log('Milestones count: ' + (milestones ? milestones.length : 0));

            // Shared column labels
            var LABELS = {
                evDate: 'Date',
                evTrailId: 'Trail',
                evMPstart: 'Start MP',
                evMPend: 'End MP',
                evImportance: 'Importance',
                description_short: 'Description',
                description_location: 'Location',
                notes: 'Notes'
            };
var currentView = { rows: [], columns: [] };
            // Table render + sorting helpers
            function renderMilestonesTable(data, columns) {
                try {
                    var container = document.getElementById('milestone-table-container');
                    if (!container) return;

                    columns = columns && columns.length ? columns : ['evDate','evMPstart','evMPend','description_location','description_short','notes'];

                    var table = document.createElement('table');
                    table.id = 'milestone-table';
                    table.style.borderCollapse = 'collapse';
                    table.style.width = '100%';

                    var thead = document.createElement('thead');
                    var headRow = document.createElement('tr');
                    columns.forEach(function (key) {
                    console.log('Adding column to header: ' + key);
                        var th = document.createElement('th');
                        th.textContent = LABELS[key] || key;
                        th.style.border = '1px solid #ccc';
                        th.style.padding = '6px 8px';
                        th.style.background = '#f7f7f7';
                        th.style.textAlign = 'left';
                        headRow.appendChild(th);
                    });
                    thead.appendChild(headRow);

                    var tbody = document.createElement('tbody');
                    (data || []).forEach(function (row) {
                        var tr = document.createElement('tr');
                        columns.forEach(function (key) {
                            var td = document.createElement('td');
                            var val = (row && (row[key] !== undefined && row[key] !== null)) ? row[key] : '';
                            td.textContent = String(val);
                            td.style.border = '1px solid #ddd';
                            td.style.padding = '6px 8px';
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    });

                    table.appendChild(thead);
                    table.appendChild(tbody);
                    container.innerHTML = '';
                    container.appendChild(table);
                } catch (e) {
                    console.error('Failed to render milestones table:', e);
                }
            }

            function parseMP(v) {
                var n = parseFloat(v);
                return isNaN(n) ? Number.POSITIVE_INFINITY : n;
            }

            function parseDateToMs(v) {
                var d = new Date(v);
                return isNaN(d.getTime()) ? 0 : d.getTime();
            }

            function sortMilestones(data, criteria) {
                var arr = (data || []).slice();
                if (criteria === 'milepost') {
                    arr.sort(function(a, b) {
                        return (parseMP(a.evMPstart) - parseMP(b.evMPstart))
                            || (parseMP(a.evMPend) - parseMP(b.evMPend))
                            || (parseDateToMs(b.evDate) - parseDateToMs(a.evDate)); // tie-breaker: newer first
                    });
                } else { // default: date
                    arr.sort(function(a, b) {
                        return parseDateToMs(b.evDate) - parseDateToMs(a.evDate); // newer first
                    });
                }
                return arr;
            }

            function getSelectedImportance() {
                var boxes = document.querySelectorAll('.importance-filter:checked');
                var vals = [];
                boxes.forEach(function (b) { vals.push(String(b.value)); });
                return vals;
            }

            function filterByImportance(data, selected) {
                if (!selected || selected.length === 0) return []; // none selected => show none
                return (data || []).filter(function (row) {
                    return selected.indexOf(String(row.evImportance)) !== -1;
                });
            }

            function getColumns() {
                var cols = ['evDate','evMPstart','evMPend', 'description_location','description_short'];
                var notesBox = document.getElementById('Notes');
                if (!notesBox || notesBox.checked) cols.push('notes');
                return cols;
            }

            function rerender() {
                var sortSelect = document.getElementById('sortOrder');
                var criteria = (sortSelect && sortSelect.value) ? sortSelect.value : 'date';
                var selectedImp = getSelectedImportance();
                var filtered = filterByImportance(milestones, selectedImp);
                var sorted = sortMilestones(filtered, criteria);
                currentView.rows = sorted;
                currentView.columns = getColumns();
                renderMilestonesTable(currentView.rows, currentView.columns);
            }

            // Initial render
            rerender();

            // React to sort changes
            var sortSelectEl = document.getElementById('sortOrder');
            if (sortSelectEl) {
                sortSelectEl.addEventListener('change', rerender);
            }

            // React to importance checkbox changes
            var impBoxes = document.querySelectorAll('.importance-filter');
            impBoxes.forEach(function (box) { box.addEventListener('change', rerender); });

            // React to Notes column toggle
            var notesToggle = document.getElementById('Notes');
            if (notesToggle) {
                notesToggle.addEventListener('change', rerender);
            }


            </script>
";
        } catch (Exception $ex) {
            $msg .= "$errorBeg E#849 error " . $ex->getMessage() . " during displayMilestones $errorEnd";
        }
        return $msg;
    } // end displayMilestones
}
