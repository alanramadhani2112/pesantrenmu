# Notification Flow Audit - SPM Fix LP2M

**Audit Date:** 2026-05-28
**Auditor:** Sisyphus
**Purpose:** Verify notification triggers match business process flow per role

## Executive Summary

This audit verifies that all notification triggers in the system align with the business process specification (docs/business-spec-flow-lp2m-v1.md) and reach the correct recipients based on their roles.

## Notification Infrastructure

**Notification Class:** app/Notifications/AkreditasiNotification.php
- Implements ShouldQueue (async dispatch)
- Channels: database, WebPush, broadcast
- Queue: 'notifications'
- Retries: 3 attempts (10s, 60s, 300s backoff)
- Failed notifications logged to failed_notifications table

**Event Listener:** app/Listeners/AkreditasiNotificationListener.php
- Listens to 8 workflow events
- Implements ShouldQueue (non-blocking)
- Registered in AppServiceProvider.php

**Events:**
1. AkreditasiTransitioned - Status changes
2. PerbaikanSubmitted - Perbaikan submitted by pesantren
3. VisitasiScheduled - Visitasi scheduled by Ketua Kelompok
4. ScoringCompleted - Scoring calculation complete
5. SKIssued - SK and certificate issued
6. BandingSubmitted - Banding submitted by pesantren
7. BandingDecided - Banding decision by admin
8. PerbaikanDeadlineApproaching - Deadline reminder


## Business Process Flow vs Notification Triggers

### Status 6 → 5: Pengajuan → Review Awal Admin

**Business Requirement:**
- Pesantren submits akreditasi
- Admin receives notification to review

**Implementation:**
- Event: AkreditasiTransitioned (6 → 5)
- Listener: handleAkreditasiTransitioned → notifyOpenForReview()
- Recipients: All Admin users (role_id = 1)
- Notification type: 'open_for_review'
- Message: "Akreditasi #{id} telah dibuka untuk verifikasi berkas"

**Status:** ✅ COMPLIANT

---

### Status 5 → 4: Review Awal Admin → Review Asesor (Assign Asesor)

**Business Requirement:**
- Admin assigns Ketua Kelompok and Anggota Kelompok
- Asesor receives notification of assignment

**Implementation:**
- Event: NOT FOUND
- Listener: NOT FOUND
- Recipients: N/A

**Status:** ❌ MISSING - No notification when asesor assigned

---

### Status 5 → 6: Review Awal Admin → Pengajuan (Return for Revision)

**Business Requirement:**
- Admin returns application for administrative revision
- Pesantren receives notification to fix issues

**Implementation:**
- Event: NOT FOUND
- Listener: NOT FOUND
- Recipients: N/A

**Status:** ❌ MISSING - No notification when returned for revision

---

### Status 4: Perbaikan Submitted

**Business Requirement:**
- Pesantren submits perbaikan after asesor review
- Ketua Kelompok and Admin receive notification

**Implementation:**
- Event: PerbaikanSubmitted
- Listener: handlePerbaikanSubmitted()
- Recipients:
  - Ketua Kelompok (Asesor tipe 1)
  - All Admin users (role_id = 1)
- Notification types: 'perbaikan_submitted', 'perbaikan_submitted_admin'
- Message: "Pesantren telah mengirimkan perbaikan dokumen"

**Status:** ✅ COMPLIANT

---

### Status 4 → 3: Review Asesor → Visitasi (Schedule Visitasi)

**Business Requirement:**
- Ketua Kelompok schedules visitasi
- Pesantren, Anggota Kelompok, and Admin receive notification

**Implementation:**
- Event: VisitasiScheduled
- Listener: handleVisitasiScheduled()
- Recipients:
  - Pesantren (akreditasi.user_id)
  - All Admin users (role_id = 1)
- Notification types: 'visitasi_scheduled', 'visitasi_scheduled_admin'
- Message: "Visitasi telah dijadwalkan"

**Status:** ⚠️ PARTIAL - Missing Anggota Kelompok notification


---

### Status 3 → 2: Visitasi → Penilaian Pasca Visitasi (Confirm Visitasi Selesai)

**Business Requirement:**
- Ketua Kelompok confirms visitasi completed
- Pesantren, Anggota Kelompok, and Admin receive notification to start post-visitasi tasks

**Implementation:**
- Event: AkreditasiTransitioned (3 → 2)
- Listener: handleAkreditasiTransitioned → notifyVisitasiSelesai()
- Recipients:
  - Pesantren (akreditasi.user_id)
  - Anggota Kelompok (Asesor tipe 2)
  - All Admin users (role_id = 1)
- Notification types: 'visitasi_selesai', 'visitasi_selesai_asesor2', 'visitasi_selesai_admin'
- Message: "Visitasi telah selesai. Tahap penilaian pasca visitasi telah dimulai"

**Status:** ✅ COMPLIANT

---

### Status 2 → 1: Penilaian Pasca Visitasi → Validasi Akhir Admin (Final Submit Asesor)

**Business Requirement:**
- Ketua Kelompok submits final asesor package
- Admin receives notification to start final validation

**Implementation:**
- Event: NOT FOUND (should be in final submit asesor method)
- Listener: NOT FOUND
- Recipients: N/A

**Status:** ❌ MISSING - No notification when asesor submits final package

---

### Status 2: Scoring Completed

**Business Requirement:**
- System calculates NK (Nilai Kelompok) after NA1 and NA2 submitted
- Admin receives notification

**Implementation:**
- Event: ScoringCompleted
- Listener: handleScoringCompleted()
- Recipients: All Admin users (role_id = 1)
- Notification type: 'scoring_completed'
- Message: "Perhitungan nilai telah selesai"

**Status:** ✅ COMPLIANT

---

### Status 1 → 0: Validasi Akhir Admin → Terakreditasi (Approve & Issue SK)

**Business Requirement:**
- Admin approves and issues SK + certificate
- Pesantren receives notification with final result

**Implementation:**
- Event: SKIssued
- Listener: handleSKIssued()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'sk_issued'
- Message: "Akreditasi Anda telah selesai. Nilai Akhir: {nilai}, Peringkat: {peringkat}, Nomor SK: {nomorSk}"

**Status:** ✅ COMPLIANT


---

### Status 5 → -1: Review Awal Admin → Ditolak (Berkas Rejected)

**Business Requirement:**
- Admin rejects at berkas verification stage
- Pesantren receives notification

**Implementation:**
- Event: AkreditasiTransitioned (5 → -1)
- Listener: handleAkreditasiTransitioned → notifyBerkasRejected()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'berkas_rejected'
- Message: "Berkas akreditasi Anda telah ditolak pada tahap Verifikasi Berkas"

**Status:** ✅ COMPLIANT

---

### Status 4 → -1: Review Asesor → Ditolak (Assessment Rejected)

**Business Requirement:**
- Asesor rejects at assessment stage
- Pesantren receives notification

**Implementation:**
- Event: AkreditasiTransitioned (4 → -1)
- Listener: handleAkreditasiTransitioned → notifyAssessmentRejected()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'assessment_rejected'
- Message: "Akreditasi Anda telah ditolak pada tahap Assessment"

**Status:** ✅ COMPLIANT

---

### Status 1 → -1: Validasi Akhir Admin → Ditolak Final

**Business Requirement:**
- Admin rejects at final validation stage
- Pesantren receives notification with option to appeal (banding)

**Implementation:**
- Event: AkreditasiTransitioned (1 → -1)
- Listener: handleAkreditasiTransitioned → notifyValidasiRejected()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'validasi_rejected'
- Message: "Akreditasi Anda telah ditolak pada tahap Validasi Admin"

**Status:** ✅ COMPLIANT

---

### Status -1 → -2: Ditolak Final → Banding (Submit Banding)

**Business Requirement:**
- Pesantren submits banding appeal
- Admin receives notification to review banding

**Implementation:**
- Event: BandingSubmitted
- Listener: handleBandingSubmitted()
- Recipients: All Admin users (role_id = 1)
- Notification type: 'banding_submitted'
- Message: "Pesantren telah mengajukan banding untuk akreditasi #{id}"

**Status:** ✅ COMPLIANT

---

### Status -2 → 1: Banding → Validasi Akhir Admin (Banding Accepted)

**Business Requirement:**
- Admin accepts banding
- Pesantren receives notification that process returns to final validation

**Implementation:**
- Event: AkreditasiTransitioned (-2 → 1)
- Listener: handleAkreditasiTransitioned → notifyBandingAcceptedValidasiAdmin()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'banding_accepted_validasi_admin'
- Message: "Banding Anda diterima. Proses akreditasi kembali ke tahap Validasi Akhir Admin"

**Status:** ✅ COMPLIANT


---

### Status -2 → -1: Banding → Ditolak Final (Banding Rejected)

**Business Requirement:**
- Admin rejects banding
- Pesantren receives notification of final rejection

**Implementation:**
- Event: AkreditasiTransitioned (-2 → -1)
- Listener: handleAkreditasiTransitioned → notifyBandingRejectedFinal()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'banding_rejected_final'
- Message: "Banding Anda telah ditolak. Akreditasi berstatus Ditolak"

**Status:** ✅ COMPLIANT

---

### Perbaikan Deadline Approaching

**Business Requirement:**
- System sends reminder when perbaikan deadline is approaching
- Pesantren receives notification

**Implementation:**
- Event: PerbaikanDeadlineApproaching
- Listener: handlePerbaikanDeadlineApproaching()
- Recipients: Pesantren (akreditasi.user_id)
- Notification type: 'perbaikan_deadline_reminder'
- Message: "Batas waktu perbaikan dokumen akan berakhir dalam {days} hari"

**Status:** ✅ COMPLIANT

---

## Summary of Findings

### ✅ COMPLIANT (11 notifications)

1. Status 6 → 5: Admin notified of new submission
2. Perbaikan submitted: Ketua Kelompok + Admin notified
3. Visitasi selesai (3 → 2): Pesantren + Anggota Kelompok + Admin notified
4. Scoring completed: Admin notified
5. SK issued (1 → 0): Pesantren notified with final result
6. Berkas rejected (5 → -1): Pesantren notified
7. Assessment rejected (4 → -1): Pesantren notified
8. Validasi rejected (1 → -1): Pesantren notified
9. Banding submitted: Admin notified
10. Banding accepted (-2 → 1): Pesantren notified
11. Banding rejected (-2 → -1): Pesantren notified
12. Perbaikan deadline reminder: Pesantren notified

### ❌ MISSING (3 critical notifications)

1. **Asesor Assignment (5 → 4)**: When admin assigns Ketua Kelompok and Anggota Kelompok, asesor should receive notification
2. **Return for Revision (5 → 6)**: When admin returns application for administrative revision, pesantren should receive notification
3. **Final Submit Asesor (2 → 1)**: When Ketua Kelompok submits final asesor package, admin should receive notification to start validation

### ⚠️ PARTIAL (1 notification)

1. **Visitasi Scheduled (4 → 3)**: Currently notifies Pesantren + Admin, but missing Anggota Kelompok notification


---

## Detailed Gap Analysis

### Gap 1: Asesor Assignment Notification (CRITICAL)

**Location:** app/Services/AkreditasiWorkflowService.php → assignAsesor() method

**Current State:**
- Method exists and assigns asesor successfully
- Transitions status from 5 → 4
- NO notification dispatched

**Required Implementation:**
- Dispatch event after asesor assignment
- Notify both Ketua Kelompok (Asesor 1) and Anggota Kelompok (Asesor 2)
- Message should include akreditasi details and their role

**Proposed Solution:**
`php
// After successful assignment in assignAsesor()
event(new AsesorAssigned(, , ));
`

**Listener:**
`php
public function handleAsesorAssigned(AsesorAssigned event): void
{
    // Notify Ketua Kelompok
    event->asesor1User->notify(new AkreditasiNotification(
        'asesor_assigned_ketua',
        'Penugasan Asesor',
        'Anda ditugaskan sebagai Ketua Kelompok untuk akreditasi pesantren.',
        '#'
    ));
    
    // Notify Anggota Kelompok
    event->asesor2User->notify(new AkreditasiNotification(
        'asesor_assigned_anggota',
        'Penugasan Asesor',
        'Anda ditugaskan sebagai Anggota Kelompok untuk akreditasi pesantren.',
        '#'
    ));
}
`

**Priority:** HIGH - Asesor needs to know they have been assigned

---

### Gap 2: Return for Revision Notification (CRITICAL)

**Location:** app/Services/AkreditasiWorkflowService.php → returnForRevision() method

**Current State:**
- Method exists and returns application to pesantren
- Transitions status from 5 → 6
- NO notification dispatched

**Required Implementation:**
- Dispatch event after return for revision
- Notify pesantren with specific issues to fix
- Include deadline if applicable

**Proposed Solution:**
`php
// After successful return in returnForRevision()
event(new PerbaikanRequested(, , ));
`

**Listener:**
`php
public function handlePerbaikanRequested(PerbaikanRequested event): void
{
    pesantrenUser = User::find(event->akreditasi->user_id);
    if (pesantrenUser) {
        pesantrenUser->notify(new AkreditasiNotification(
            'perbaikan_requested',
            'Perbaikan Diperlukan',
            'Akreditasi Anda dikembalikan untuk perbaikan administratif. Silakan perbaiki dan submit ulang.',
            '#'
        ));
    }
}
`

**Priority:** HIGH - Pesantren needs to know application was returned


---

### Gap 3: Final Submit Asesor Notification (CRITICAL)

**Location:** app/Services/AssessorScoringService.php → finalSubmitAsesor() method

**Current State:**
- Method exists and submits final asesor package
- Transitions status from 2 → 1
- NO notification dispatched

**Required Implementation:**
- Dispatch event after final submit
- Notify all admin users to start final validation
- Include summary of completed assessments

**Proposed Solution:**
`php
// After successful final submit in finalSubmitAsesor()
event(new AsesorPackageSubmitted());
`

**Listener:**
`php
public function handleAsesorPackageSubmitted(AsesorPackageSubmitted event): void
{
    admins = User::where('role_id', 1)->get();
    if (admins->isNotEmpty()) {
        Notification::send(admins, new AkreditasiNotification(
            'asesor_package_submitted',
            'Paket Asesor Lengkap',
            'Ketua Kelompok telah menyelesaikan penilaian. Silakan lakukan validasi akhir.',
            '#'
        ));
    }
}
`

**Priority:** HIGH - Admin needs to know assessment is ready for validation

---

### Gap 4: Anggota Kelompok Missing from Visitasi Scheduled (MEDIUM)

**Location:** app/Listeners/AkreditasiNotificationListener.php → handleVisitasiScheduled()

**Current State:**
- Notifies Pesantren ✅
- Notifies Admin ✅
- Does NOT notify Anggota Kelompok ❌

**Required Implementation:**
- Add Anggota Kelompok to notification recipients
- Anggota needs to know visitasi schedule

**Proposed Solution:**
`php
public function handleVisitasiScheduled(VisitasiScheduled event): void
{
    // ... existing pesantren notification ...
    
    // ADD: Notify Anggota Kelompok
    assessment2 = Assessment::where('akreditasi_id', akreditasi->id)
        ->where('tipe', 2)
        ->with('asesor')
        ->first();
    
    if (assessment2 && assessment2->asesor) {
        asesor2User = User::find(assessment2->asesor->user_id);
        if (asesor2User) {
            asesor2User->notify(new AkreditasiNotification(
                'visitasi_scheduled_asesor2',
                'Visitasi Dijadwalkan',
                'Visitasi telah dijadwalkan. Tanggal: ' . event->scheduleData['tanggal_mulai'],
                '#'
            ));
        }
    }
    
    // ... existing admin notification ...
}
`

**Priority:** MEDIUM - Anggota Kelompok should be informed but not critical


---

## Recommendations

### Immediate Actions (Priority: HIGH)

1. **Create Missing Events**
   - AsesorAssigned event (dispatched when admin assigns asesor)
   - PerbaikanRequested event (dispatched when admin returns for revision)
   - AsesorPackageSubmitted event (dispatched when Ketua Kelompok submits final package)

2. **Update AkreditasiNotificationListener**
   - Add handleAsesorAssigned() method
   - Add handlePerbaikanRequested() method
   - Add handleAsesorPackageSubmitted() method
   - Update handleVisitasiScheduled() to include Anggota Kelompok

3. **Register New Events in AppServiceProvider**
   - Add event listeners for the 3 new events

4. **Update Workflow Services**
   - AkreditasiWorkflowService::assignAsesor() - dispatch AsesorAssigned
   - AkreditasiWorkflowService::returnForRevision() - dispatch PerbaikanRequested
   - AssessorScoringService::finalSubmitAsesor() - dispatch AsesorPackageSubmitted

### Testing Requirements

1. **Unit Tests**
   - Test each new event is dispatched correctly
   - Test each listener receives correct data
   - Test notification recipients are correct per role

2. **Integration Tests**
   - Test full workflow with notification verification
   - Test notification delivery to correct users
   - Test notification content matches business requirements

3. **Manual QA**
   - Verify notifications appear in UI
   - Verify WebPush notifications work
   - Verify notification links navigate correctly

---

## Compliance Matrix

| Workflow Stage | Notification Required | Status | Priority |
|---|---|---|---|
| Pengajuan (6 → 5) | Admin notified | ✅ COMPLIANT | - |
| Assign Asesor (5 → 4) | Asesor notified | ❌ MISSING | HIGH |
| Return for Revision (5 → 6) | Pesantren notified | ❌ MISSING | HIGH |
| Perbaikan Submitted | Ketua + Admin notified | ✅ COMPLIANT | - |
| Visitasi Scheduled (4 → 3) | Pesantren + Asesor + Admin | ⚠️ PARTIAL | MEDIUM |
| Visitasi Selesai (3 → 2) | Pesantren + Asesor + Admin | ✅ COMPLIANT | - |
| Final Submit Asesor (2 → 1) | Admin notified | ❌ MISSING | HIGH |
| Scoring Completed | Admin notified | ✅ COMPLIANT | - |
| SK Issued (1 → 0) | Pesantren notified | ✅ COMPLIANT | - |
| Berkas Rejected (5 → -1) | Pesantren notified | ✅ COMPLIANT | - |
| Assessment Rejected (4 → -1) | Pesantren notified | ✅ COMPLIANT | - |
| Validasi Rejected (1 → -1) | Pesantren notified | ✅ COMPLIANT | - |
| Banding Submitted (-1 → -2) | Admin notified | ✅ COMPLIANT | - |
| Banding Accepted (-2 → 1) | Pesantren notified | ✅ COMPLIANT | - |
| Banding Rejected (-2 → -1) | Pesantren notified | ✅ COMPLIANT | - |
| Perbaikan Deadline | Pesantren notified | ✅ COMPLIANT | - |

**Overall Compliance: 75% (12/16 complete)**

---

## Conclusion

The notification system is **75% compliant** with business requirements. The infrastructure is solid (async queue, retry logic, failed notification tracking), but **3 critical notification triggers are missing** and **1 notification has incomplete recipients**.

**Critical gaps:**
1. Asesor not notified when assigned
2. Pesantren not notified when application returned for revision
3. Admin not notified when asesor submits final package

**Impact:** Users miss important workflow transitions, leading to delays and confusion.

**Effort to fix:** ~4 hours (3 new events + 4 listener methods + tests)

**Recommendation:** Fix all 4 gaps before production deployment.

---

**Audit completed:** 2026-05-28
**Next review:** After implementing fixes

