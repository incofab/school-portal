@extends('layouts.website')

@section('title', 'Privacy Policy | EduManager')
@section('meta_description', 'Learn how EduManager collects, uses, stores, and protects information across its school management platform, including QR code attendance.')
@section('meta_keywords', 'EduManager privacy policy, school data privacy, QR attendance privacy')

@push('styles')
  <style>
    .policy-hero { background: linear-gradient(135deg, var(--brand-soft), #fff 72%); border-bottom: 1px solid var(--line); padding: 4rem 0 3rem; }
    .policy-hero h1 { color: var(--brand-deep); font-size: clamp(2.1rem, 5vw, 3.4rem); letter-spacing: -.04em; line-height: 1.12; margin: .5rem 0 1rem; }
    .policy-hero p { color: var(--muted); font-size: 1.08rem; margin: 0; max-width: 760px; }
    .policy-content { display: grid; gap: 2rem; grid-template-columns: minmax(0, 1fr) 250px; margin: 0 auto; max-width: 1180px; padding: 3.5rem 1.25rem 5rem; }
    .policy-article { max-width: 820px; }
    .policy-article section { border-bottom: 1px solid var(--line); padding: 0 0 1.7rem; margin: 0 0 1.7rem; scroll-margin-top: 100px; }
    .policy-article h2 { color: var(--brand-deep); font-size: 1.45rem; line-height: 1.3; margin: 0 0 .75rem; }
    .policy-article p, .policy-article li { color: var(--muted); }
    .policy-article ul { padding-left: 1.25rem; }
    .policy-article li + li { margin-top: .4rem; }
    .policy-aside { align-self: start; background: var(--surface-soft); border: 1px solid var(--line); border-radius: var(--radius); padding: 1.25rem; position: sticky; top: 100px; }
    .policy-aside strong { color: var(--brand-deep); display: block; margin-bottom: .75rem; }
    .policy-aside a { color: var(--brand-dark); display: block; font-size: .92rem; padding: .3rem 0; }
    .policy-aside a:hover, .policy-aside a:focus-visible { color: var(--accent); text-decoration: underline; }
    @media (max-width: 760px) { .policy-content { grid-template-columns: 1fr; padding-top: 2.25rem; } .policy-aside { position: static; } .policy-aside nav { columns: 2; } .policy-hero { padding: 3rem 0 2.25rem; } }
    @media (max-width: 420px) { .policy-aside nav { columns: 1; } }
  </style>
@endpush

@section('content')
  <section class="policy-hero">
    <div class="site-container">
      <p>Privacy and trust</p>
      <h1>Privacy Policy</h1>
      <p>This policy explains how EduManager handles information when schools, their staff, learners, parents, and other users use our school management platform and related services.</p>
      <p><small>Effective date: 11 November 2026</small></p>
    </div>
  </section>

  <div class="policy-content">
    <article class="policy-article">
      <section id="scope"><h2>1. Scope and roles</h2><p>EduManager provides digital tools to help schools manage educational and administrative activities. In many cases, the school decides what learner, parent, and staff information is entered and why; the school is responsible for those decisions and EduManager processes that information to provide the service. EduManager may act independently as a controller for account administration, platform security, service improvement, and business communications. Schools should make this policy available to their communities and provide any additional notices required for their use of the platform.</p></section>
      <section id="information"><h2>2. Information we handle</h2><p>Depending on enabled features and school configuration, information may include:</p><ul><li>Account and contact details such as names, email addresses, phone numbers, roles, and login credentials.</li><li>School records such as learner profiles, class and enrollment details, attendance, assessment and result records, fee and payment records, and communications.</li><li>Staff information needed to administer accounts, assign responsibilities, and record school operations.</li><li>Technical and usage information such as device and browser details, log data, IP address, session information, and service diagnostics.</li><li>Information submitted in support requests, forms, or other interactions with us.</li></ul><p>We do not ask users to provide information that a school does not need for its chosen workflows. Schools should avoid entering sensitive personal information unless the feature and purpose require it.</p></section>
      <section id="attendance"><h2>3. Attendance and QR code scanning</h2><p>Where a school uses the attendance app, authorized school staff can use a device camera and QR code scanner to record attendance. A scan may read a QR identifier associated with a learner or attendance record and send it to the service with the relevant school, class, session, date, time, and staff account context so that an attendance entry can be created or checked. The QR code is a convenient lookup mechanism; schools should not encode unnecessary personal information in it.</p><p>Camera access is used for scanning when the staff member invokes that function and is subject to the device and operating-system permission settings. Attendance records can reveal a learner's presence, absence, and patterns over time. Access should therefore be limited to staff with a legitimate school responsibility, devices should be protected, and schools should explain attendance practices to learners and families. Staff should verify scan results and use the school's correction process when a scan is inaccurate. EduManager does not use attendance scans to identify people for advertising.</p></section>
      <section id="purposes"><h2>4. How information is used</h2><p>We use information to provide and operate school features; authenticate users and apply school access controls; maintain attendance, academic, finance, and communication workflows; respond to support requests; protect against fraud, abuse, and security incidents; troubleshoot and improve reliability; meet legal obligations; and communicate service notices. We use information only as appropriate to these purposes and the school's instructions where the school controls the records.</p></section>
      <section id="sharing"><h2>5. When information is shared</h2><p>Information may be available to authorized users within the relevant school according to their roles and permissions. We may use vetted hosting, storage, communications, analytics, payment, and support providers to operate the service; they may process information on our behalf under appropriate safeguards. We may disclose information when legally required, to protect users or the service, or as part of a business transfer subject to applicable law. We do not sell personal information.</p></section>
      <section id="security"><h2>6. Security and retention</h2><p>We use administrative, technical, and organizational measures designed to protect information against unauthorized access, loss, alteration, or disclosure. No online service can guarantee absolute security. Schools and users also help protect information by using strong credentials, keeping devices secure, assigning least-privilege access, and promptly reporting suspected incidents. We retain information while needed to provide the service, meet documented school instructions, resolve disputes, enforce agreements, and comply with legal obligations. Retention and deletion may depend on school settings and applicable law.</p></section>
      <section id="rights"><h2>7. Choices and privacy rights</h2><p>Users should normally contact their school to access, correct, export, or request deletion of learner, parent, staff, or attendance records managed by that school. We will support the school in responding as appropriate. For information EduManager controls directly, users may contact us using the details below. Depending on location, individuals may have rights to access, correct, delete, restrict, object to, or receive a copy of personal information, and may complain to a data protection authority. We may need to verify identity and preserve certain records where law permits or requires.</p></section>
      <section id="children"><h2>8. Children and learners</h2><p>EduManager is provided to schools and is not intended for children to independently create public accounts. Schools and, where applicable, parents or guardians are responsible for appropriate notices, permissions, and supervision for learner use. We process learner information to support school-directed educational and administrative purposes.</p></section>
      <section id="transfers"><h2>9. International processing and third-party services</h2><p>Service providers or infrastructure may be located in countries other than the user's location. Where information is transferred across borders, we take steps intended to protect it in accordance with applicable data protection requirements. The platform may link to third-party services; their own privacy notices govern information they collect directly.</p></section>
      <section id="changes"><h2>10. Changes to this policy</h2><p>We may update this policy as the service, legal requirements, or our practices change. We will revise the effective date above and provide notice through the platform or another reasonable channel when a material change warrants it. Continued use after an update is subject to the revised policy, to the extent permitted by law.</p></section>
      <section id="contact"><h2>11. Contact</h2><p>For questions, privacy requests, or concerns, contact EduManager at <a href="mailto:{{ config('app.email') }}">{{ config('app.email') }}</a>. If you are a learner, parent, or staff member asking about school-managed records, please include your school name so we can direct your request appropriately.</p></section>
    </article>
    <aside class="policy-aside" aria-label="Privacy policy contents"><strong>On this page</strong><nav>@foreach ([['scope','Scope and roles'],['information','Information we handle'],['attendance','QR attendance'],['purposes','How we use information'],['sharing','Sharing'],['security','Security and retention'],['rights','Your rights'],['children','Learners'],['transfers','International processing'],['changes','Policy changes'],['contact','Contact']] as [$id, $label])<a href="#{{ $id }}">{{ $label }}</a>@endforeach</nav></aside>
  </div>
@endsection
