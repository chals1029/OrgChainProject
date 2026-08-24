@extends('org.layout')

@section('title', $submission->exists ? ($submission->isOffCampus() ? 'Edit Local Off-campus Activity' : 'Edit In-campus Activity') : 'Create Activity Proposal')

@section('header')
    <h1 id="pageHeaderTitle">{{ $submission->exists ? ($submission->isOffCampus() ? 'Edit Local Off-campus Activity' : 'Edit In-campus Activity') : 'Create an Activity' }}</h1>
    <p class="org-welcome" id="pageHeaderDesc">Prepare the complete activity request, documents, and compliance attachments in 3 guided steps.</p>
@endsection

@section('actions')
    <a href="{{ route('office.activities') }}" class="org-btn org-btn-ghost">
        <i class="bi bi-arrow-left"></i> Back to activities
    </a>
@endsection

@section('content')
    @php
        $activity = $submission->activity;
        $attachments = $submission->attachments ?? [];
        $conditions = $attachments['conditions'] ?? [];
        $isEditing = $submission->exists;
        $currentType = old('activity_type', $submission->activity_type ?: 'in_campus');

        $programmeTemplate = '<h2>PROGRAMME</h2><p><strong>Date:</strong> [Date] &nbsp; <strong>Time:</strong> [Start time – End time]</p><p><strong>Venue:</strong> [Location]</p><table><thead><tr><th>Time</th><th>Activity</th><th>Person in charge</th></tr></thead><tbody><tr><td>8:00 AM – 8:15 AM</td><td>Attendance and Registration</td><td>[Name]</td></tr><tr><td>8:15 AM – 8:20 AM</td><td>Opening Prayer</td><td>[Name]</td></tr><tr><td>8:20 AM – 8:30 AM</td><td>Opening Remarks</td><td>[Name]</td></tr><tr><td>[Time]</td><td>[Activity]</td><td>[Name]</td></tr></tbody></table><p><strong>Masters of Ceremony:</strong> [Name/s]</p>';
        $proposalTemplate = '<h2>PROJECT PROPOSAL</h2><p><strong>A. Title of the Project:</strong><br>[Activity title]</p><p><strong>B. Rationale:</strong><br>[Explain the need and expected value of the activity.]</p><p><strong>Objectives:</strong></p><ul><li>[Objective 1]</li><li>[Objective 2]</li></ul><p><strong>C. Description of the Project, Strategies, and Methods:</strong><br>[Describe the implementation.]</p><p><strong>D. Participants Involved:</strong><br>[Participants]</p><p><strong>E. Program Duration/Date:</strong><br>[Date and duration]</p><p><strong>F. Safety Measures / Emergency Preparedness Plan:</strong><br>[Safety plan]</p><p><br><strong>Prepared by:</strong><br>[President, Organization]</p><p><strong>Noted by:</strong><br>[Adviser, Organization]</p>';
        $budgetTemplate = '<h2>BUDGET PROPOSAL</h2><p><strong>A. Funding Requirements</strong></p><p>The total projected expenses needed to hold this event are as follows:</p><table><thead><tr><th>Item / Description</th><th>Quantity</th><th>Unit Cost</th><th>Total</th></tr></thead><tbody><tr><td>[Item]</td><td>[Qty]</td><td>₱0.00</td><td>₱0.00</td></tr><tr><td><strong>Total Projected Expense</strong></td><td></td><td></td><td><strong>₱0.00</strong></td></tr></tbody></table><p><strong>B. Source of Funds</strong></p><table><thead><tr><th>Source</th><th>Amount</th></tr></thead><tbody><tr><td>[Source of funds]</td><td>₱0.00</td></tr></tbody></table><p><br><strong>Prepared by:</strong><br>[Treasurer, Organization]</p><p><strong>Noted by:</strong><br>[President] &nbsp; [Adviser]</p>';
        $facultyTemplate = '<h2>DUTIES AND RESPONSIBILITIES OF FACULTY-IN-CHARGE</h2><p><strong>Activity:</strong> [Activity title]</p><p>You are hereby designated as person-in-charge of the event titled <strong>[Activity title]</strong> on [date] at [location].</p><p><strong>BEFORE the activity:</strong></p><ul><li>Assist in planning by checking the programme and flow of activities.</li><li>Assist officers and members in disseminating activity details.</li><li>Conduct a meeting with officers regarding the activity.</li><li>Orient students about the schedule and activity details.</li></ul><p><strong>DURING the activity:</strong></p><ul><li>Ensure scheduled activities are executed as planned.</li><li>Monitor student activities.</li></ul><p><strong>AFTER the activity:</strong></p><ul><li>Conduct an evaluation or assessment.</li><li>Discuss evaluation results and financial aspects with officers.</li><li>Ensure the narrative report and documentation are completed.</li></ul><p><strong>Prepared by:</strong><br>[Authorized signatory]</p><p><strong>Conformed:</strong><br>[Faculty-in-Charge / Adviser]</p>';
        $medicalRequestTemplate = '<h2>MEDICAL REQUEST LETTER</h2><p><strong>Date:</strong> [Date]</p><p><strong>To:</strong> Director / Head, University Medical and Dental Services<br>Batangas State University</p><p><strong>Subject:</strong> Request for Medical Clearance / Medical Personnel Assistance</p><p>Dear Sir/Madam,</p><p>The <strong>[Organization Name]</strong> will be conducting an in-campus activity titled <strong>"[Activity Title]"</strong> on <strong>[Date]</strong> at <strong>[Location / Venue]</strong>.</p><p>In this regard, we respectfully request medical clearance and/or the presence of medical standby personnel to ensure the safety and wellbeing of all participating students during the event.</p><p>Thank you for your usual support.</p><p><br><strong>Prepared by:</strong><br>[Activity Chair / Officer]</p><p><strong>Noted by:</strong><br>[Faculty Adviser]</p>';
        $insuranceRequestTemplate = '<h2>INSURANCE REQUEST LETTER</h2><p><strong>Date:</strong> [Date]</p><p><strong>To:</strong> Head, Student Services Office / Insurance Section<br>Batangas State University</p><p><strong>Subject:</strong> Request for Student Group Accident Insurance Coverage</p><p>Dear Sir/Madam,</p><p>Greetings!</p><p>The <strong>[Organization Name]</strong> will hold an activity titled <strong>"[Activity Title]"</strong> on <strong>[Date]</strong> at <strong>[Location]</strong> with approximately <strong>[Number of Participants]</strong> student participants.</p><p>We kindly request student group personal accident insurance coverage for all attendees listed in the attached roster.</p><p>Thank you for your prompt assistance.</p><p><br><strong>Prepared by:</strong><br>[Treasurer / Representative]</p><p><strong>Noted by:</strong><br>[President] &nbsp; [Faculty Adviser]</p>';
        $resolutionTemplate = '<h2>RESOLUTION OF THE ORGANIZATION</h2><p><strong>RESOLUTION NO. [001-2026]</strong></p><p><strong>A RESOLUTION APPROVING THE CONDUCT OF "[ACTIVITY TITLE]" AND ALLOCATING THE NECESSARY FUNDS THEREOF.</strong></p><p><strong>WHEREAS,</strong> the <strong>[Organization Name]</strong> aims to foster student leadership, development, and academic excellence;</p><p><strong>WHEREAS,</strong> the proposed activity titled <strong>"[Activity Title]"</strong> has been planned for <strong>[Date]</strong> at <strong>[Location]</strong>;</p><p><strong>NOW THEREFORE, BE IT RESOLVED AS IT IS HEREBY RESOLVED,</strong> that the Officers of [Organization Name] unanimously approve the conduct of the aforementioned activity and authorize the allocation of funds as stated in the official Budget Proposal.</p><p><br><strong>UNANIMOUSLY APPROVED:</strong><br>[President, Student Organization]<br>[Vice President] &nbsp; [Secretary]</p>';
        $sampleLetterTemplate = '<h2>SAMPLE REQUEST LETTER</h2><p><strong>Date:</strong> [Date]</p><p><strong>To:</strong> Office of Student Affairs / Campus Director<br>Batangas State University</p><p><strong>Subject:</strong> Permission to Conduct In-Campus Activity</p><p>Dear Sir/Madam,</p><p>Greetings of Peace!</p><p>The <strong>[Organization Name]</strong> respectfully requests permission to conduct an in-campus activity titled <strong>"[Activity Title]"</strong> on <strong>[Date]</strong> at <strong>[Location / Venue]</strong> from <strong>[Start Time]</strong> to <strong>[End Time]</strong>.</p><p>This activity aims to [Brief description of purpose and expected output].</p><p>We assure full compliance with university guidelines, safety protocols, and waste management policies.</p><p>Thank you very much and we look forward to your favorable response.</p><p><br><strong>Respectfully yours,</strong><br>[President, Student Organization]</p><p><strong>Recommending Approval:</strong><br>[Faculty Adviser]</p>';

        $wpcfTemplate = '<h2>WASTE POLICY COMPLIANCE FORM (WPCF)</h2><p><strong>Sustainable Development Office / Environmental Management</strong></p><p><strong>Name of Organization:</strong> [Organization Name]</p><p><strong>Title of Activity:</strong> [Activity Title]</p><p><strong>Date & Venue:</strong> [Date] at [Location]</p><p><strong>Expected Waste Generation & Management Commitment:</strong></p><table><thead><tr><th>Waste Category</th><th>Estimated Amount</th><th>Disposal / Segregation Plan</th></tr></thead><tbody><tr><td>Biodegradable (Food waste)</td><td>[Low / Med / High]</td><td>Segregated in green bins</td></tr><tr><td>Recyclables (Paper, plastic bottles)</td><td>[Low / Med / High]</td><td>Collected for recycling drive</td></tr><tr><td>Residual Waste</td><td>[Low / Med / High]</td><td>Properly bagged and disposed</td></tr></tbody></table><p>We hereby commit to strict compliance with BatStateU Solid Waste Management Policies.</p><p><br><strong>Submitted by:</strong><br>[Organization President / Representative]</p>';
        $approvedPlanTemplate = '<h2>APPROVED PLAN OF ACTIVITIES</h2><p><strong>Academic Year:</strong> 2025–2026</p><p><strong>Name of Organization:</strong> [Organization Name]</p><table><thead><tr><th>Quarter / Month</th><th>Planned Activity Title</th><th>Target Date</th><th>Status</th></tr></thead><tbody><tr><td>Q1 / Semester 1</td><td>[Activity Title]</td><td>[Date]</td><td>In Progress</td></tr></tbody></table><p>This plan aligns with the officially approved organization recognition/renewal submission.</p>';
        $classScheduleTemplate = '<h2>CLASS SCHEDULE & PARTICIPANT MANIFEST</h2><p><strong>Activity Title:</strong> [Activity Title]</p><p><strong>Organization:</strong> [Organization Name]</p><table><thead><tr><th>No.</th><th>Student Name</th><th>SR Code</th><th>Course & Year</th><th>Class Schedule Conflict Clearance</th></tr></thead><tbody><tr><td>1</td><td>[Student Name 1]</td><td>[21-00000]</td><td>[BS CS 3rd Year]</td><td>Cleared — No class conflict</td></tr><tr><td>2</td><td>[Student Name 2]</td><td>[22-00000]</td><td>[BS IT 2nd Year]</td><td>Cleared — No class conflict</td></tr></tbody></table>';
        $meetingMinutesTemplate = '<h2>MINUTES OF MEETING & ATTENDANCE</h2><p><strong>Activity Consultation & Officers Meeting</strong></p><p><strong>Date & Time:</strong> [Meeting Date] &nbsp; <strong>Venue:</strong> [Meeting Venue / Online]</p><p><strong>Agenda:</strong></p><ol><li>Planning and approval for [Activity Title].</li><li>Budget allocation and safety protocols.</li></ol><p><strong>Key Discussions & Resolutions:</strong><br>[Summarize discussion and approved motions.]</p><p><strong>Attendance List:</strong></p><table><thead><tr><th>Name</th><th>Position / Role</th><th>Signature</th></tr></thead><tbody><tr><td>[Officer 1]</td><td>President</td><td>Signed</td></tr><tr><td>[Officer 2]</td><td>Adviser</td><td>Signed</td></tr></tbody></table>';

        $offCampusReqTemplate = '<h2>REQUEST FOR THE CONDUCT OF LOCAL OFF-CAMPUS ACTIVITIES (FO-REQ-09)</h2><p><strong>Date of Application:</strong> [Date]</p><p><strong>Name of Organization:</strong> [Organization Name]</p><p><strong>Title of Activity:</strong> [Activity Title]</p><p><strong>Target Destination:</strong> [Location / Destination]</p><p><strong>Inclusive Dates:</strong> [Start Date] to [End Date]</p><p><strong>Target Participants:</strong> [Number and Description of Students]</p><p><strong>Learning Objectives & Justification:</strong></p><ul><li>[Objective 1]</li><li>[Objective 2]</li></ul><p><strong>Transportation & Safety Plan:</strong><br>[Details on transport provider, emergency contact, and safety protocol]</p><p><br><strong>Prepared by:</strong><br>[Activity Chair / Student Representative]</p><p><strong>Recommending Approval:</strong><br>[Faculty Adviser / Department Head]</p>';
        $certComplianceTemplate = '<h2>CERTIFICATE OF COMPLIANCE</h2><p>This is to certify that <strong>[Organization Name]</strong> has fully complied with all safety, administrative, and institutional requirements for the conduct of the local off-campus activity titled <strong>[Activity Title]</strong> scheduled on [Start Date] at [Location].</p><p>We confirm that:</p><ol><li>Parent/Guardian Consent Forms (Waivers) have been collected from all participating students.</li><li>Group Insurance Coverage has been secured for all participants.</li><li>Medical clearances and safety protocols have been duly established.</li><li>Accompanied by designated Faculty-in-Charge at all times.</li></ol><p><br><strong>Certified Correct:</strong><br>[President, Student Organization]</p><p><strong>Attested by:</strong><br>[Faculty-in-Charge / Adviser]</p>';
        $chedReportTemplate = '<h2>CHED COMPLIANCE REPORT</h2><p><strong>Institution:</strong> Batangas State University</p><p><strong>Activity Name:</strong> [Activity Title]</p><p><strong>Destination:</strong> [Location]</p><p><strong>CHED Memorandum Order (CMO) Compliance Summary:</strong></p><table><thead><tr><th>Requirement Item</th><th>Status / Details</th></tr></thead><tbody><tr><td>Curricular / Non-Curricular Alignment</td><td>Compliant — Integrated with learning objectives</td></tr><tr><td>Risk Assessment & Emergency Plan</td><td>Completed and attached</td></tr><tr><td>First Aid & Medical Preparedness</td><td>Medical kit & certified first-aider assigned</td></tr><tr><td>Parental Consent & Insurance</td><td>100% submission verified</td></tr></tbody></table><p><br><strong>Submitted by:</strong><br>[Organization President / Adviser]</p>';
        $travelMatrixTemplate = '<h2>MATRIX OF TRAVEL AND TOUR</h2><p><strong>Activity:</strong> [Activity Title]</p><p><strong>Destination:</strong> [Destination Address]</p><table><thead><tr><th>Date & Time</th><th>Location / Venue</th><th>Activity Description</th><th>Person Responsible</th></tr></thead><tbody><tr><td>[Day 1 07:00 AM]</td><td>Assembly at Campus</td><td>Student briefing & head count</td><td>[Faculty-in-Charge]</td></tr><tr><td>[Day 1 08:00 AM]</td><td>Departure to Destination</td><td>Travel via accredited transport</td><td>[Driver / Tour Guide]</td></tr><tr><td>[Day 1 10:00 AM]</td><td>Arrival & Site Orientation</td><td>Educational tour / Workshop</td><td>[Activity Lead]</td></tr></tbody></table><p><strong>Tour Operator / Transport Contact:</strong> [Name & Phone Number]</p>';
        $passengerMatrixTemplate = '<h2>FORMAT FOR MATRIX OF PASSENGER</h2><p><strong>Vehicle Unit / Bus No.:</strong> [Bus 1 / Vehicle Plate No.]</p><table><thead><tr><th>No.</th><th>Student / Participant Name</th><th>Course & Year</th><th>Emergency Contact Person</th><th>Contact Number</th></tr></thead><tbody><tr><td>1</td><td>[Student Name 1]</td><td>[BS CS 3rd Year]</td><td>[Parent Name]</td><td>[0917-000-0000]</td></tr><tr><td>2</td><td>[Student Name 2]</td><td>[BS IT 2nd Year]</td><td>[Parent Name]</td><td>[0918-000-0000]</td></tr></tbody></table><p><strong>Assigned Bus Captain / Faculty:</strong> [Name of Overseer]</p>';
        $courseActivitiesTemplate = '<h2>COURSE ACTIVITIES SCHEDULE</h2><p><strong>Course Code & Title:</strong> [e.g., IT 314 - Field Work / Off-Campus Seminar]</p><p><strong>Faculty Instructor:</strong> [Instructor Name]</p><p><strong>Academic Objectives:</strong></p><ul><li>Apply classroom theoretical knowledge in industry/field environments.</li><li>Conduct direct observations and produce an experiential synthesis report.</li></ul><p><strong>Assessment / Evaluation Method:</strong><br>[Post-activity report / reflection paper due 5 days after conduct.]</p>';
    @endphp

    @if (session('success'))
        <div class="org-alert org-alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    <section class="org-wizard-intro liquid-glass">
        <div>
            <span class="org-eyebrow" id="workflowEyebrow"><i class="bi {{ $submission->isOffCampus() ? 'bi-geo-alt-fill' : 'bi-building-check' }}"></i> Activity request</span>
            <h2 id="workflowTitle">{{ $submission->isOffCampus() ? 'Local off-campus activity workflow' : 'In-campus activity workflow' }}</h2>
            <p id="workflowDesc">Complete the checklist from the Office of Student Organization before sending your activity for review.</p>
        </div>
        <span class="org-status org-status-{{ $submission->status === 'submitted' ? 'verification' : 'created' }}">
            {{ $submission->status === 'submitted' ? 'Submitted for review' : 'Draft' }}
        </span>
    </section>

    <form method="post" action="{{ $isEditing ? route('office.activities.update', $submission) : route('office.activities.store') }}" enctype="multipart/form-data" class="org-activity-wizard" id="activityWizard">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <input type="hidden" name="submission_action" id="submissionAction" value="draft">

        <ol class="org-wizard-steps" aria-label="Activity request steps">
            <li class="is-active" data-step-indicator="1"><span>1</span><strong>Activity information</strong></li>
            <li data-step-indicator="2"><span>2</span><strong>Editable documents</strong></li>
            <li data-step-indicator="3"><span>3</span><strong>Review, print & submit</strong></li>
        </ol>

        @if ($errors->any())
            <div class="org-alert"><i class="bi bi-exclamation-triangle-fill"></i> Please correct the highlighted information before saving.</div>
        @endif

        <!-- STEP 1: Activity Information -->
        <section class="org-wizard-panel liquid-glass is-active" data-wizard-step="1">
            <div class="org-wizard-panel-head">
                <div><span class="org-step-kicker">Step 1 of 3</span><h2>Activity information</h2></div>
                <p>Choose the request type and fill in the core activity details.</p>
            </div>
            <div class="org-form-grid">
                <label class="org-form-field org-form-field-wide">
                    <span>Activity type <b>*</b></span>
                    <select name="activity_type" id="activityType" required>
                        <option value="in_campus" @selected($currentType === 'in_campus')>In-campus activity</option>
                        <option value="local_off_campus" @selected($currentType === 'local_off_campus')>Local Off-campus activity</option>
                    </select>
                    <small id="activityTypeHelp">Select the category to generate the relevant official document templates and checklist requirements.</small>
                </label>
                <label class="org-form-field">
                    <span>Organization name</span>
                    <input type="text" name="organization_name" id="inputOrgName" value="{{ old('organization_name', $submission->organization_name) }}" placeholder="e.g., Supreme Student Council">
                </label>
                <label class="org-form-field">
                    <span>Activity title <b>*</b></span>
                    <input type="text" name="title" id="inputTitle" value="{{ old('title', $activity?->title) }}" required placeholder="e.g., Leadership Summit 2026">
                    @error('title') <em>{{ $message }}</em> @enderror
                </label>
                <label class="org-form-field">
                    <span>Start date and time <b>*</b></span>
                    <input type="datetime-local" name="starts_at" id="inputStartsAt" required value="{{ old('starts_at', $activity?->starts_at?->format('Y-m-d\TH:i')) }}">
                    @error('starts_at') <em>{{ $message }}</em> @enderror
                </label>
                <label class="org-form-field">
                    <span>End date and time</span>
                    <input type="datetime-local" name="ends_at" id="inputEndsAt" value="{{ old('ends_at', $activity?->ends_at?->format('Y-m-d\TH:i')) }}">
                    @error('ends_at') <em>{{ $message }}</em> @enderror
                </label>
                <label class="org-form-field org-form-field-wide">
                    <span>Venue / destination location <b>*</b></span>
                    <input type="text" name="location" id="inputLocation" required value="{{ old('location', $activity?->location) }}" placeholder="e.g., Gymnasium / Tagaytay City, Cavite">
                    @error('location') <em>{{ $message }}</em> @enderror
                </label>
                <label class="org-form-field org-form-field-wide">
                    <span>Rationale</span>
                    <textarea name="rationale" rows="4" placeholder="Why is this activity needed?">{{ old('rationale', $submission->rationale) }}</textarea>
                </label>
                <label class="org-form-field org-form-field-wide">
                    <span>Objectives</span>
                    <textarea name="objectives" rows="4" placeholder="List the intended outcomes.">{{ old('objectives', $submission->objectives) }}</textarea>
                </label>
                <label class="org-form-field">
                    <span>Participants involved</span>
                    <textarea name="participants" rows="4" placeholder="Who will participate?">{{ old('participants', $submission->participants) }}</textarea>
                </label>
                <label class="org-form-field">
                    <span>Safety / emergency preparedness plan</span>
                    <textarea name="safety_plan" rows="4" placeholder="Describe safety measures and emergency procedures.">{{ old('safety_plan', $submission->safety_plan) }}</textarea>
                </label>
            </div>
            <div class="org-wizard-actions">
                <button type="button" class="org-btn org-btn-primary" data-next-step>Continue to documents <i class="bi bi-arrow-right"></i></button>
            </div>
        </section>

        <!-- STEP 2: Core Documents -->
        <section class="org-wizard-panel liquid-glass" data-wizard-step="2" hidden>
            <div class="org-wizard-panel-head">
                <div><span class="org-step-kicker">Step 2 of 3</span><h2>Create the core documents</h2></div>
                <p>Edit the supplied document templates directly. Their content is saved with this request.</p>
            </div>
            <div class="org-editor-notice"><i class="bi bi-info-circle-fill"></i> Replace the bracketed placeholders with your activity details. Tables and formatting can be customized using the toolbar.</div>

            <!-- In-Campus Editors (All 12 In-Campus Documents) -->
            <div class="org-editor-stack" data-type-group="in_campus" @if($currentType !== 'in_campus') style="display: none;" @endif>
                <label class="org-editor-field"><span><i class="bi bi-recycle"></i> 1. Waste Policy Compliance Form (WPCF)</span><textarea id="editor_in_wpcf" class="tinymce-editor" name="wpcf_html">{{ old('wpcf_html', $submission->wpcf_html ?: $wpcfTemplate) }}</textarea>@error('wpcf_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-calendar-week"></i> 2. Programme <b>*</b></span><textarea id="editor_in_programme" class="tinymce-editor" name="programme_html">{{ old('programme_html', $submission->programme_html ?: $programmeTemplate) }}</textarea>@error('programme_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-file-earmark-text"></i> 3. Project Proposal <b>*</b></span><textarea id="editor_in_proposal" class="tinymce-editor" name="project_proposal_html">{{ old('project_proposal_html', $submission->project_proposal_html ?: $proposalTemplate) }}</textarea>@error('project_proposal_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-cash-stack"></i> 4. Budget Proposal <b>*</b></span><textarea id="editor_in_budget" class="tinymce-editor" name="budget_proposal_html">{{ old('budget_proposal_html', $submission->budget_proposal_html ?: $budgetTemplate) }}</textarea>@error('budget_proposal_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-person-badge"></i> 5. Faculty-in-Charge Designation <b>*</b></span><textarea id="editor_in_faculty" class="tinymce-editor" name="faculty_in_charge_html">{{ old('faculty_in_charge_html', $submission->faculty_in_charge_html ?: $facultyTemplate) }}</textarea>@error('faculty_in_charge_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-envelope-paper-fill"></i> 6. Medical Request Sample Letter</span><textarea id="editor_in_medical_req" class="tinymce-editor" name="medical_request_html">{{ old('medical_request_html', $submission->medical_request_html ?: $medicalRequestTemplate) }}</textarea>@error('medical_request_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-shield-lock-fill"></i> 7. Insurance Request Sample Letter</span><textarea id="editor_in_insurance_req" class="tinymce-editor" name="insurance_request_html">{{ old('insurance_request_html', $submission->insurance_request_html ?: $insuranceRequestTemplate) }}</textarea>@error('insurance_request_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-file-earmark-check-fill"></i> 8. Resolution of the Organization</span><textarea id="editor_in_resolution" class="tinymce-editor" name="resolution_html">{{ old('resolution_html', $submission->resolution_html ?: $resolutionTemplate) }}</textarea>@error('resolution_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-file-earmark-word-fill"></i> 9. Sample Request Letter</span><textarea id="editor_in_sample_letter" class="tinymce-editor" name="sample_letter_html">{{ old('sample_letter_html', $submission->sample_letter_html ?: $sampleLetterTemplate) }}</textarea>@error('sample_letter_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-journal-bookmark-fill"></i> 10. Approved Plan of Activities</span><textarea id="editor_in_approved_plan" class="tinymce-editor" name="approved_plan_html">{{ old('approved_plan_html', $submission->approved_plan_html ?: $approvedPlanTemplate) }}</textarea>@error('approved_plan_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-clock-history"></i> 11. Class Schedule & Participant Roster</span><textarea id="editor_in_class_schedule" class="tinymce-editor" name="class_schedule_html">{{ old('class_schedule_html', $submission->class_schedule_html ?: $classScheduleTemplate) }}</textarea>@error('class_schedule_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-people-fill"></i> 12. Minutes of Meeting & Attendance Record</span><textarea id="editor_in_meeting_minutes" class="tinymce-editor" name="meeting_minutes_html">{{ old('meeting_minutes_html', $submission->meeting_minutes_html ?: $meetingMinutesTemplate) }}</textarea>@error('meeting_minutes_html') <em>{{ $message }}</em> @enderror</label>
            </div>

            <!-- Local Off-Campus Editors -->
            <div class="org-editor-stack" data-type-group="local_off_campus" @if($currentType !== 'local_off_campus') style="display: none;" @endif>
                <label class="org-editor-field"><span><i class="bi bi-file-earmark-word-fill"></i> 1. Request for Conduct of Local Off-Campus Activities (FO-REQ-09) <b>*</b></span><textarea id="editor_off_req" class="tinymce-editor" name="off_campus_req_html">{{ old('off_campus_req_html', $submission->off_campus_req_html ?: $offCampusReqTemplate) }}</textarea>@error('off_campus_req_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-award-fill"></i> 2. Certificate of Compliance <b>*</b></span><textarea id="editor_off_cert" class="tinymce-editor" name="cert_compliance_html">{{ old('cert_compliance_html', $submission->cert_compliance_html ?: $certComplianceTemplate) }}</textarea>@error('cert_compliance_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-journal-check"></i> 3. CHED Compliance Report <b>*</b></span><textarea id="editor_off_ched" class="tinymce-editor" name="ched_report_html">{{ old('ched_report_html', $submission->ched_report_html ?: $chedReportTemplate) }}</textarea>@error('ched_report_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-map-fill"></i> 4. Matrix of Travel and Tour <b>*</b></span><textarea id="editor_off_travel" class="tinymce-editor" name="travel_matrix_html">{{ old('travel_matrix_html', $submission->travel_matrix_html ?: $travelMatrixTemplate) }}</textarea>@error('travel_matrix_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-people-fill"></i> 5. Format for Matrix of Passenger <b>*</b></span><textarea id="editor_off_passenger" class="tinymce-editor" name="passenger_matrix_html">{{ old('passenger_matrix_html', $submission->passenger_matrix_html ?: $passengerMatrixTemplate) }}</textarea>@error('passenger_matrix_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-card-checklist"></i> 6. Course Activities Schedule <b>*</b></span><textarea id="editor_off_course" class="tinymce-editor" name="course_activities_html">{{ old('course_activities_html', $submission->course_activities_html ?: $courseActivitiesTemplate) }}</textarea>@error('course_activities_html') <em>{{ $message }}</em> @enderror</label>
                <label class="org-editor-field"><span><i class="bi bi-person-badge"></i> 7. Faculty-in-Charge Designation <b>*</b></span><textarea id="editor_off_faculty" class="tinymce-editor" name="faculty_in_charge_html">{{ old('faculty_in_charge_html', $submission->faculty_in_charge_html ?: $facultyTemplate) }}</textarea>@error('faculty_in_charge_html') <em>{{ $message }}</em> @enderror</label>
            </div>

            <div class="org-wizard-actions">
                <button type="button" class="org-btn org-btn-ghost" data-previous-step><i class="bi bi-arrow-left"></i> Back</button>
                <button type="button" class="org-btn org-btn-primary" data-next-step>Review, print & submit <i class="bi bi-arrow-right"></i></button>
            </div>
        </section>

        <!-- STEP 3: Review, Print & Submit -->
        <section class="org-wizard-panel liquid-glass" data-wizard-step="3" hidden>
            <div class="org-wizard-panel-head">
                <div><span class="org-step-kicker">Step 3 of 3</span><h2>Review, print & submit</h2></div>
                <p>Review your activity summary, print your compiled documents, and upload required attachments.</p>
            </div>

            <!-- Review Summary Card -->
            <article class="org-review-summary-card">
                <div class="org-review-head">
                    <div>
                        <span class="org-submission-type" id="reviewTypeBadge">
                            <i class="bi {{ $submission->isOffCampus() ? 'bi-geo-alt-fill' : 'bi-building-check' }}"></i>
                            {{ $submission->isOffCampus() ? 'Local off-campus activity' : 'In-campus activity' }}
                        </span>
                        <h3 id="reviewTitle">{{ $activity?->title ?: 'Untitled Activity' }}</h3>
                    </div>
                    <button type="button" class="org-btn org-btn-primary" id="printDocsBtn">
                        <i class="bi bi-printer-fill"></i> Print / Download documents
                    </button>
                </div>
                <div class="org-review-meta">
                    <p><strong><i class="bi bi-building"></i> Organization:</strong> <span id="reviewOrg">{{ $submission->organization_name ?: 'Not specified' }}</span></p>
                    <p><strong><i class="bi bi-geo-alt"></i> Location:</strong> <span id="reviewLocation">{{ $activity?->location ?: 'Not specified' }}</span></p>
                    <p><strong><i class="bi bi-calendar3"></i> Date:</strong> <span id="reviewDates">{{ $activity?->starts_at?->format('M j, Y g:i A') ?: 'Not set' }}</span></p>
                </div>
            </article>

            <!-- Attachments & Checklist Section -->
            <div class="org-section-heading" style="margin-top: 1.5rem;">
                <div>
                    <span class="org-eyebrow"><i class="bi bi-shield-check"></i> Conditional Attachments</span>
                    <h3>Upload supporting files if applicable</h3>
                </div>
            </div>

            <!-- Conditional Attachments -->
            <!-- In-Campus Conditions -->
            <div class="org-condition-grid" data-type-group="in_campus" @if($currentType !== 'in_campus') style="display: none;" @endif>
                @foreach (['medical' => ['Physical activity or sports', 'Medical Clearance'], 'insurance' => ['Travel or physical activity', 'Insurance'], 'guest_speaker' => ['Guest speaker or judges', 'Curriculum Vitae'], 'late_or_weekend' => ['Beyond 10 PM or weekend schedule', 'Notarized Waiver / Parental Consent'], 'university_facility' => ['Using a university facility', 'Reservation Form']] as $condition => [$label, $document])
                    @php $requirement = collect($inCampusRequirements)->firstWhere('condition', $condition); $key = $requirement['key']; @endphp
                    <article class="org-condition-card">
                        <label class="org-check-toggle">
                            <input type="hidden" name="conditions[{{ $condition }}]" value="0">
                            <input type="checkbox" name="conditions[{{ $condition }}]" value="1" @checked(old("conditions.$condition", $conditions[$condition] ?? false))>
                            <span></span>
                            <strong>{{ $label }}</strong>
                        </label>
                        <p>When selected, <b>{{ $document }}</b> becomes required on submission.</p>
                        <label class="org-upload-field">
                            <span>Upload {{ $document }}</span>
                            <input type="file" name="attachments[{{ $key }}]" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                            @if (!empty($attachments[$key]['name'])) <small><i class="bi bi-paperclip"></i> Current: {{ $attachments[$key]['name'] }}</small> @endif
                        </label>
                    </article>
                @endforeach
            </div>

            <!-- Local Off-Campus Conditions -->
            <div class="org-condition-grid" data-type-group="local_off_campus" @if($currentType !== 'local_off_campus') style="display: none;" @endif>
                @foreach (['medical' => ['Physical activities or field work', 'Medical Clearance / Health Declaration'], 'insurance' => ['Group accident coverage', 'Group Personal Accident Insurance Policy'], 'transport' => ['Hired vehicle or bus transport', 'Vehicle Registration & Driver License'], 'tour_operator' => ['Third-party tour provider', 'DOT Accreditation & Tour Contract']] as $condition => [$label, $document])
                    @php $requirement = collect($offCampusRequirements)->firstWhere('condition', $condition); $key = $requirement['key']; @endphp
                    <article class="org-condition-card">
                        <label class="org-check-toggle">
                            <input type="hidden" name="conditions[{{ $condition }}]" value="0">
                            <input type="checkbox" name="conditions[{{ $condition }}]" value="1" @checked(old("conditions.$condition", $conditions[$condition] ?? false))>
                            <span></span>
                            <strong>{{ $label }}</strong>
                        </label>
                        <p>When selected, <b>{{ $document }}</b> becomes required on submission.</p>
                        <label class="org-upload-field">
                            <span>Upload {{ $document }}</span>
                            <input type="file" name="attachments[{{ $key }}]" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                            @if (!empty($attachments[$key]['name'])) <small><i class="bi bi-paperclip"></i> Current: {{ $attachments[$key]['name'] }}</small> @endif
                        </label>
                    </article>
                @endforeach
            </div>

            <!-- Off-Campus Core Checklist -->
            <div class="org-checklist" data-type-group="local_off_campus" @if($currentType !== 'local_off_campus') style="display: none;" @endif style="margin-top: 1.5rem;">
                <h4 style="margin-bottom: 0.8rem; color: var(--org-red);">Core Requirement Checklist</h4>
                @foreach ($offCampusRequirements as $index => $requirement)
                    @continue(in_array($requirement['group'], ['editor', 'conditional'], true))
                    @php $key = $requirement['key']; @endphp
                    <article class="org-checklist-item">
                        <span class="org-checklist-number">{{ $index + 1 }}</span>
                        <div><h3>{{ $requirement['title'] }} <b>*</b></h3><p>{{ $requirement['description'] }}</p></div>
                        <label class="org-upload-field">
                            <span>Choose file</span>
                            <input type="file" name="attachments[{{ $key }}]" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                            @if (!empty($attachments[$key]['name'])) <small><i class="bi bi-paperclip"></i> {{ $attachments[$key]['name'] }}</small> @endif
                        </label>
                    </article>
                @endforeach
            </div>

            @error('attachments') <div class="org-alert"><i class="bi bi-exclamation-triangle-fill"></i> {{ $message }}</div> @enderror

            <div class="org-wizard-actions">
                <button type="button" class="org-btn org-btn-ghost" data-previous-step><i class="bi bi-arrow-left"></i> Back</button>
                <button type="submit" class="org-btn org-btn-ghost" data-save-draft><i class="bi bi-save"></i> Save draft</button>
                <button type="submit" class="org-btn org-btn-primary" data-submit-activity><i class="bi bi-send-fill"></i> Submit for review</button>
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const panels = [...document.querySelectorAll('[data-wizard-step]')];
            const indicators = [...document.querySelectorAll('[data-step-indicator]')];
            const activityTypeSelect = document.getElementById('activityType');
            let currentStep = 1;

            const initEditors = () => {
                if (!window.tinymce) return;

                window.tinymce.triggerSave();
                window.tinymce.remove();

                const activeType = activityTypeSelect ? activityTypeSelect.value : 'in_campus';
                const activeContainer = document.querySelector(`.org-editor-stack[data-type-group="${activeType}"]`);
                
                if (activeContainer) {
                    const textareas = activeContainer.querySelectorAll('textarea.tinymce-editor');
                    textareas.forEach(textarea => {
                        window.tinymce.init({
                            target: textarea,
                            height: 380,
                            menubar: false,
                            promotion: false,
                            plugins: 'lists link table code wordcount',
                            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | alignleft aligncenter alignright | table link | removeformat code',
                            content_style: 'body { font-family: Instrument Sans, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #1a0a0d; padding: 12px; } table { width: 100%; border-collapse: collapse; } td, th { border: 1px solid #d9c4c8; padding: 8px; } th { background: #f8eef0; }',
                            setup: (editor) => {
                                editor.on('change keyup input blur', () => {
                                    editor.save();
                                });
                            }
                        });
                    });
                }
            };

            const updateTypeGroups = (type) => {
                document.querySelectorAll('[data-type-group]').forEach(el => {
                    const isMatch = el.dataset.typeGroup === type;
                    el.style.display = isMatch ? '' : 'none';
                    el.querySelectorAll('input, select, textarea').forEach(input => {
                        input.disabled = !isMatch;
                    });
                });

                const isOffCampus = type === 'local_off_campus';
                const workflowEyebrow = document.getElementById('workflowEyebrow');
                const workflowTitle = document.getElementById('workflowTitle');
                if (workflowEyebrow) {
                    workflowEyebrow.innerHTML = `<i class="bi ${isOffCampus ? 'bi-geo-alt-fill' : 'bi-building-check'}"></i> Activity request`;
                }
                if (workflowTitle) {
                    workflowTitle.textContent = isOffCampus ? 'Local off-campus activity workflow' : 'In-campus activity workflow';
                }

                if (currentStep === 2) {
                    setTimeout(initEditors, 50);
                }
            };

            const updateReviewSummary = () => {
                const titleVal = document.getElementById('inputTitle')?.value || 'Untitled Activity';
                const orgVal = document.getElementById('inputOrgName')?.value || 'Not specified';
                const locVal = document.getElementById('inputLocation')?.value || 'Not specified';
                const dateVal = document.getElementById('inputStartsAt')?.value || 'Not set';
                const typeVal = activityTypeSelect?.value || 'in_campus';
                const isOffCampus = typeVal === 'local_off_campus';

                const reviewTitle = document.getElementById('reviewTitle');
                const reviewOrg = document.getElementById('reviewOrg');
                const reviewLocation = document.getElementById('reviewLocation');
                const reviewDates = document.getElementById('reviewDates');
                const reviewBadge = document.getElementById('reviewTypeBadge');

                if (reviewTitle) reviewTitle.textContent = titleVal;
                if (reviewOrg) reviewOrg.textContent = orgVal;
                if (reviewLocation) reviewLocation.textContent = locVal;
                if (reviewDates) reviewDates.textContent = dateVal ? new Date(dateVal).toLocaleString() : 'Not set';
                if (reviewBadge) {
                    reviewBadge.innerHTML = `<i class="bi ${isOffCampus ? 'bi-geo-alt-fill' : 'bi-building-check'}"></i> ${isOffCampus ? 'Local off-campus activity' : 'In-campus activity'}`;
                }
            };

            const printCompiledDocs = () => {
                if (window.tinymce) {
                    window.tinymce.triggerSave();
                }

                const activeType = activityTypeSelect ? activityTypeSelect.value : 'in_campus';
                const activeContainer = document.querySelector(`.org-editor-stack[data-type-group="${activeType}"]`);
                if (!activeContainer) return;

                const textareas = activeContainer.querySelectorAll('textarea.tinymce-editor');
                let compiledHtml = '';

                textareas.forEach(ta => {
                    const val = ta.value || '';
                    if (val.trim()) {
                        compiledHtml += `<div class="doc-section">${val}</div>`;
                    }
                });

                if (!compiledHtml) {
                    alert('No document content available to print.');
                    return;
                }

                const titleVal = document.getElementById('inputTitle')?.value || 'Activity Documents';
                const printWin = window.open('', '_blank');
                if (!printWin) {
                    alert('Please allow popups to print documents.');
                    return;
                }

                printWin.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Compiled Activity Documents - ${titleVal}</title>
                        <style>
                            @page { size: A4; margin: 20mm; }
                            body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; margin: 0; padding: 20px; }
                            h1, h2, h3 { text-align: center; text-transform: uppercase; margin-bottom: 15px; }
                            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                            th, td { border: 1px solid #000; padding: 6px 10px; text-align: left; font-size: 11pt; }
                            th { background-color: #f2f2f2; font-weight: bold; }
                            .doc-section { page-break-after: always; margin-bottom: 30px; }
                            .doc-section:last-child { page-break-after: auto; }
                            @media print {
                                .doc-section { page-break-after: always; }
                            }
                        </style>
                    </head>
                    <body>
                        ${compiledHtml}
                        <script>
                            window.onload = function() {
                                window.print();
                            };
                        <\/script>
                    </body>
                    </html>
                `);
                printWin.document.close();
            };

            document.getElementById('printDocsBtn')?.addEventListener('click', printCompiledDocs);

            activityTypeSelect?.addEventListener('change', (e) => {
                updateTypeGroups(e.target.value);
            });

            const showStep = (step) => {
                currentStep = Math.min(Math.max(step, 1), panels.length);
                panels.forEach((panel) => {
                    const active = Number(panel.dataset.wizardStep) === currentStep;
                    panel.hidden = !active;
                    panel.classList.toggle('is-active', active);
                });
                indicators.forEach((indicator) => indicator.classList.toggle('is-active', Number(indicator.dataset.stepIndicator) <= currentStep));
                
                if (currentStep === 2) {
                    setTimeout(initEditors, 50);
                } else if (currentStep === 3) {
                    updateReviewSummary();
                }

                document.querySelector('.org-content')?.scrollTo({top: 0, behavior: 'smooth'});
            };

            document.querySelectorAll('[data-next-step]').forEach((button) => button.addEventListener('click', () => showStep(currentStep + 1)));
            document.querySelectorAll('[data-previous-step]').forEach((button) => button.addEventListener('click', () => showStep(currentStep - 1)));
            document.querySelector('[data-save-draft]')?.addEventListener('click', () => {
                document.getElementById('submissionAction').value = 'draft';
                window.tinymce?.triggerSave();
            });
            document.querySelector('[data-submit-activity]')?.addEventListener('click', () => {
                document.getElementById('submissionAction').value = 'submit';
                window.tinymce?.triggerSave();
            });
            document.getElementById('activityWizard')?.addEventListener('submit', () => window.tinymce?.triggerSave());

            if (activityTypeSelect) {
                updateTypeGroups(activityTypeSelect.value);
            }
        });
    </script>
@endpush
