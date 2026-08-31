import { formatDate, statusLabel } from '@/utils/formatters';

const DASH = '—';

function hours(value) {
    return Number(value || 0).toFixed(1);
}

function time(value) {
    if (!value) return DASH;
    const [hour, minute] = String(value).split(':').map(Number);
    if (Number.isNaN(hour)) return value;
    return new Date(2000, 0, 1, hour, minute || 0).toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
    });
}

function dateTime(value, part) {
    if (!value) return DASH;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return DASH;
    return part === 'time'
        ? date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
        : formatDate(date);
}

function DetailRow({ leftLabel, leftValue, rightLabel, rightValue }) {
    return (
        <tr>
            <th>{leftLabel}</th>
            <td>{leftValue || DASH}</td>
            <th>{rightLabel}</th>
            <td>{rightValue || DASH}</td>
        </tr>
    );
}

function ApprovalRecord({ number, title, approved, signer, date, note, children }) {
    return (
        <section className="print-approval-record">
            <div className="print-approval-heading">
                <span>{number}</span>
                <strong>{title}</strong>
            </div>
            <p>
                Status:{' '}
                <b className={approved ? 'print-approved' : 'print-pending'}>
                    {approved ? 'Approved' : 'Pending'}
                </b>
            </p>
            {children}
            <p className="print-signed-by">Signed by:</p>
            <p>{signer || DASH}</p>
            <p className="print-signature">{approved ? signer || DASH : DASH}</p>
            {note && <p className="print-muted">{note}</p>}
            <div className="print-approval-date">
                <p><b>Date:</b> {dateTime(date, 'date')}</p>
                <p><b>Time:</b> {dateTime(date, 'time')}</p>
            </div>
        </section>
    );
}

export default function PrintableTimesheet({ timesheet, totals }) {
    const worker = timesheet.worker || {};
    const project = timesheet.project || {};
    const days = timesheet.day_entries || [];
    const equipment = timesheet.equipment_entries || [];
    const submitted = Boolean(timesheet.submitted_at);
    const managerApproved = Boolean(timesheet.manager_approved_at);
    const clientApproved = Boolean(timesheet.client_approved_at);
    const accommodationConfirmed = Boolean(timesheet.compliance?.attachments);
    const printedAt = new Date();
    const finalTotals = { ...timesheet, ...totals };

    return (
        <article className="timesheet-print" aria-hidden="true">
            <header className="print-document-header">
                <div className="print-logo">
                    <strong>Lodge<span>X</span></strong>
                    <small>Crew Hub</small>
                </div>
                <h1>Worker Timesheet</h1>
            </header>

            <div className="print-document-grid">
                <main className="print-main-column">
                    <table className="print-details-table">
                        <tbody>
                            <DetailRow
                                leftLabel="Worker Name:"
                                leftValue={worker.full_name}
                                rightLabel="Supervisor / Manager:"
                                rightValue={timesheet.supervisor_name}
                            />
                            <DetailRow
                                leftLabel="Worker ID:"
                                leftValue={worker.employee_id}
                                rightLabel="Week Ending:"
                                rightValue={formatDate(timesheet.period_end)}
                            />
                            <DetailRow
                                leftLabel="Company:"
                                leftValue={worker.company}
                                rightLabel="Payroll Period:"
                                rightValue={`${formatDate(timesheet.period_start)} – ${formatDate(timesheet.period_end)}`}
                            />
                            <DetailRow
                                leftLabel="Position / Trade:"
                                leftValue={worker.position}
                                rightLabel="Cost Code / Job Code:"
                                rightValue={project.code}
                            />
                            <DetailRow
                                leftLabel="Major Project:"
                                leftValue={project.name}
                                rightLabel="Status:"
                                rightValue={timesheet.status_label || statusLabel(timesheet.status)}
                            />
                            <DetailRow
                                leftLabel="Project Manager:"
                                leftValue={timesheet.manager_approver_name || timesheet.supervisor_name}
                                rightLabel=""
                                rightValue=""
                            />
                            <DetailRow
                                leftLabel="Project Number:"
                                leftValue={project.code}
                                rightLabel=""
                                rightValue=""
                            />
                        </tbody>
                    </table>

                    <section className="print-section">
                        <h2>Daily Time Entry</h2>
                        <table className="print-data-table print-time-table">
                            <thead>
                                <tr>
                                    <th>Date</th><th>Day</th><th>Shift</th><th>Start</th><th>End</th>
                                    <th>Break<br />(hrs)</th><th>Regular<br />Hours</th><th>Overtime<br />Hours</th>
                                    <th>Double<br />Time</th><th>Travel<br />Hours</th><th>Standby<br />Hours</th>
                                    <th>Total<br />Hours</th><th>Work Location /<br />Area</th><th>Task / Activity</th><th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                {days.map((row, index) => (
                                    <tr key={row.date || index}>
                                        <td>{formatDate(row.date, { month: 'short', day: 'numeric', year: undefined })}</td>
                                        <td>{row.day_label || DASH}</td><td>{row.shift || DASH}</td>
                                        <td>{time(row.start_time)}</td><td>{time(row.end_time)}</td>
                                        <td>{hours(row.break_hours)}</td><td>{hours(row.regular_hours)}</td>
                                        <td>{hours(row.overtime_hours)}</td><td>{hours(row.double_time_hours)}</td>
                                        <td>{hours(row.travel_hours)}</td><td>{hours(row.standby_hours)}</td>
                                        <td>{hours(row.total_hours)}</td><td>{row.work_location || DASH}</td>
                                        <td>{row.task || DASH}</td><td>{row.notes || DASH}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colSpan="5">Weekly Totals</th>
                                    <td>{hours(finalTotals.break_hours)}</td><td>{hours(finalTotals.regular_hours)}</td>
                                    <td>{hours(finalTotals.overtime_hours)}</td><td>{hours(finalTotals.double_time_hours)}</td>
                                    <td>{hours(finalTotals.travel_hours)}</td><td>{hours(finalTotals.standby_hours)}</td>
                                    <td>{hours(finalTotals.hours)}</td><td colSpan="3" />
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    <div className="print-equipment-summary">
                        <section className="print-section">
                            <h2>Equipment Usage</h2>
                            <table className="print-data-table print-equipment-table">
                                <thead>
                                    <tr>
                                        <th>Day / Date</th><th>Equipment Type</th><th>Unit / Asset ID</th>
                                        <th>Start Time</th><th>End Time</th><th>Hours</th><th>Cost Code</th>
                                        <th>Work Activity</th><th>Meter / Fuel / Usage Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {equipment.map((row, index) => (
                                        <tr key={row.id || index}>
                                            <td>{row.day || DASH}, {formatDate(row.date, { month: 'short', day: 'numeric', year: undefined })}</td>
                                            <td>{row.equipment_type || DASH}</td><td>{row.unit_id || DASH}</td>
                                            <td>{time(row.start_time)}</td><td>{time(row.end_time)}</td>
                                            <td>{hours(row.hours)}</td><td>{row.cost_code || DASH}</td>
                                            <td>{row.work_activity || DASH}</td><td>{row.fuel_notes || DASH}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </section>

                        <section className="print-weekly-summary">
                            <h2>Weekly Summary</h2>
                            <dl>
                                <div><dt>Regular Hours:</dt><dd>{hours(finalTotals.regular_hours)}</dd></div>
                                <div><dt>Overtime Hours:</dt><dd>{hours(finalTotals.overtime_hours)}</dd></div>
                                <div><dt>Travel Hours:</dt><dd>{hours(finalTotals.travel_hours)}</dd></div>
                                <div><dt>Standby Hours:</dt><dd>{hours(finalTotals.standby_hours)}</dd></div>
                                <div><dt>Equipment Hours:</dt><dd>{hours(finalTotals.equipment_hours)}</dd></div>
                                <div className="print-week-total"><dt>Weekly Total:</dt><dd>{hours(finalTotals.hours)}</dd></div>
                            </dl>
                        </section>
                    </div>
                    <p className="print-note">Note: Equipment hours may differ from total time due to idle time, changeovers, or multi-equipment use.</p>

                    <section className="print-declarations">
                        <div>
                            <h2>Worker Declaration</h2>
                            <p>I declare that the information provided on this timesheet is true and correct to the best of my knowledge and that all hours worked, equipment used, and activities performed are accurately recorded.</p>
                            <p className="print-label">Worker Signature:</p>
                            <p className="print-signature">{timesheet.worker_signature || DASH}</p>
                            <p><b>Date:</b> {dateTime(timesheet.worker_signed_at || timesheet.submitted_at, 'date')}</p>
                            <p><b>Time:</b> {dateTime(timesheet.worker_signed_at || timesheet.submitted_at, 'time')}</p>
                        </div>
                        <div className="print-comments">
                            <section><h2>Worker Notes</h2><p>{timesheet.worker_comment || 'No worker notes provided.'}</p></section>
                            <section><h2>Manager Comments</h2><p>{timesheet.manager_comment || 'No manager comments provided.'}</p>
                                <p className="print-signature">{timesheet.manager_approver_name || timesheet.supervisor_name || DASH}</p>
                            </section>
                        </div>
                        <div>
                            <h2>Client Comments <small>(Optional)</small></h2>
                            <p>{timesheet.client_comment || 'No client comments provided.'}</p>
                            <p className="print-label">Client Signature:</p>
                            <p className="print-signature">{timesheet.client_approver_name || DASH}</p>
                            <p><b>Date:</b> {dateTime(timesheet.client_approved_at, 'date')}</p>
                            <p><b>Time:</b> {dateTime(timesheet.client_approved_at, 'time')}</p>
                        </div>
                    </section>
                </main>

                <aside className="print-approval-column">
                    <h2>Approval Record</h2>
                    <ApprovalRecord number="1" title="Worker Approved" approved={submitted}
                        signer={timesheet.worker_signature || worker.full_name} date={timesheet.submitted_at} />
                    <ApprovalRecord number="2" title="Accommodations Confirmed" approved={accommodationConfirmed}
                        signer={accommodationConfirmed ? 'AI Verified' : null} date={timesheet.submitted_at}>
                        <div className="print-ai-verified">◉ AI Verified</div>
                        <p className="print-muted">Accommodation verification status recorded by Crew Hub.</p>
                    </ApprovalRecord>
                    <ApprovalRecord number="3" title="Approved by Manager" approved={managerApproved}
                        signer={timesheet.manager_approver_name || timesheet.supervisor_name}
                        date={timesheet.manager_approved_at} note="Site Supervisor" />
                    {timesheet.client_approval_required && (
                        <ApprovalRecord number="4" title="Approved by Client" approved={clientApproved}
                            signer={timesheet.client_approver_name} date={timesheet.client_approved_at}
                            note={project.name} />
                    )}
                </aside>
            </div>

            <footer className="print-document-footer">
                <span>Crew Hub Timesheet Record</span>
                <span>Page 1 of 1</span>
                <span>Printed: {printedAt.toLocaleDateString()} · {printedAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>
            </footer>
        </article>
    );
}
