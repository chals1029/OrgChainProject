<?php

namespace App\Services;

class ActivityRequirements
{
    public function inCampusRequirements(): array
    {
        return [
            ['key' => 'checklist_template', 'title' => 'Checklist Template', 'description' => 'Complete the official in-campus activity checklist.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['checklist', 'template'], 'source_file' => '0. Checklist Template.docx'],
            ['key' => 'programme', 'title' => 'Programme', 'description' => 'Final programme with dates, times, venues, and activity flow.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['programme', 'program', 'schedule'], 'source_file' => '2. Programme.docx'],
            ['key' => 'project_proposal', 'title' => 'Project Proposal', 'description' => 'Proposal prepared by the organization president and noted by the adviser.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['project', 'proposal'], 'source_file' => '3. Project Proposal.docx'],
            ['key' => 'budget_proposal', 'title' => 'Budget Proposal', 'description' => 'Itemized funding requirements and source of funds.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['budget', 'fund'], 'source_file' => '4. Budget Proposal.docx'],
            ['key' => 'medical_clearance', 'title' => 'Medical Request Sample Letter', 'description' => 'Complete and sign the official medical request letter.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['medical', 'request'], 'source_file' => '5. Medical Request Sample Letter.docx'],
            ['key' => 'insurance', 'title' => 'Insurance Request Sample Letter', 'description' => 'Complete and sign the official insurance request letter.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['insurance', 'request'], 'source_file' => '6. Insurance Request Sample Letter.docx'],
            ['key' => 'resolution', 'title' => 'Resolution of the Organization', 'description' => 'Officer-signed resolution for the activity.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['resolution'], 'source_file' => '7. Resolution of the Organization .docx'],
            ['key' => 'faculty_in_charge', 'title' => 'Faculty-in-Charge', 'description' => 'Signed designation of the faculty member in charge.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['faculty', 'charge', 'designation'], 'source_file' => '14. Faculty-In-Charge.docx'],
            ['key' => 'sample_letter', 'title' => 'Sample Letter', 'description' => 'Signed activity request letter addressed to the appropriate university office.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['sample', 'letter', 'request'], 'source_file' => 'Sample Letter.docx'],
            ['key' => 'wpcf', 'title' => 'Waste Policy Compliance Form (WPCF)', 'description' => 'Complete the official waste policy compliance form.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['wpcf', 'waste', 'policy'], 'source_file' => 'Waste-Policy-Compliance-Form-2026 (1).doc'],
        ];
    }

    public function localOffCampusRequirements(): array
    {
        return [
            ['key' => 'course_activities', 'title' => 'Course Activities', 'description' => 'Course activity plan showing learning objectives and syllabus relevance.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['course', 'activity', 'syllabus'], 'source_file' => '13. Course Activities (1).docx'],
            ['key' => 'off_campus_req', 'title' => 'Request for Conduct of Local Off-Campus Activities (FO-REQ-09)', 'description' => 'Official request form with participants, itinerary, faculty-in-charge, fees, and approvals.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['req', 'off', 'campus'], 'source_file' => 'BatStateU-FO-REQ-09_Request-for-the-Conduct-of-Local-Off-Campus-Activities-Rev.-02 (1) (1).docx'],
            ['key' => 'parents_consent', 'title' => 'Parent Consent Form / Waiver (FO-SOA-03)', 'description' => 'Completed parent or guardian consent form for student participants.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['parent', 'consent', 'waiver'], 'source_file' => 'BatStateU-FO-SOA-03_Parent_s Consent Form (Waiver)_Rev. 01.doc'],
            ['key' => 'cert_compliance', 'title' => 'Certificate of Compliance', 'description' => 'Signed certification of compliance for the off-campus activity.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['certificate', 'compliance'], 'source_file' => 'Certificate of Compliance (3).docx'],
            ['key' => 'checklist_requirements', 'title' => 'Checklist of the Requirements', 'description' => 'Complete the official local off-campus checklist.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['checklist', 'requirements'], 'source_file' => 'Checklist of the Requirements.docx'],
            ['key' => 'ched_report', 'title' => 'CHED Compliance Report', 'description' => 'Complete the official CHED compliance report.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['ched', 'compliance', 'report'], 'source_file' => 'CHED Compliance Report (1).docx'],
            ['key' => 'travel_matrix', 'title' => 'Matrix of Travel and Tour', 'description' => 'Complete travel itinerary with destinations, times, and arrangements.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['travel', 'tour', 'itinerary'], 'source_file' => 'Copy of Matrix of Travel and Tour (1).docx'],
            ['key' => 'faculty_in_charge', 'title' => 'Faculty-in-Charge', 'description' => 'Signed designation of the faculty member in charge.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['faculty', 'charge', 'designation'], 'source_file' => 'Faculty-In-Charge.docx'],
            ['key' => 'passenger_matrix', 'title' => 'Matrix of Passenger', 'description' => 'Completed passenger manifest and contact details.', 'group' => 'before', 'phase' => 'Before activity', 'required_on_submit' => true, 'tokens' => ['passenger', 'matrix'], 'source_file' => 'FORMAT FOR MATRIX OF PASSENGER.docx'],
        ];
    }
}

