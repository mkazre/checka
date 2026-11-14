// Central config for all external links, API endpoints and form actions.
// The platform serves this file with the domain swapped in automatically
// (admin panel → Settings → General → Root domain), so it also works on a test domain.
window.CHECKA_CONFIG = {
  marketingBase: 'https://checkaplus.co.za',
  appBase: 'https://app.checkaplus.co.za',
  signupPostUrl: 'https://app.checkaplus.co.za/signup',
  findWorkspacePostUrl: 'https://app.checkaplus.co.za/find-workspace',
  plansApiUrl: 'https://app.checkaplus.co.za/api/plans',
  workspaceAvailabilityUrl: 'https://app.checkaplus.co.za/api/workspace-availability',
  contactFormUrl: 'https://app.checkaplus.co.za/api/contact',
  loginRedirect: function (workspace) { return 'https://' + workspace + '.checkaplus.co.za' + '/login'; },
  workspaceDomainSuffix: '.checkaplus.co.za',
  contactEmail: 'sales@wheelsturn.co.za',
  salesPhone: '010 023 2189',
  altPhone: '010 102 7555',
  offices: [
    { name: 'Roodepoort · West Rand', lines: ['Shop 63, Princess Crossing', '54 Ontdekkers Road, Roodepoort'] },
    { name: 'Boksburg · East Rand', lines: ['Unit 5, 4 Herman Pieters Street', 'Hughes Ext 13, Boksburg'] }
  ],
  businessHours: 'Mon–Fri 7:30–17:00 · Sat 7:30–14:00 · Sun closed'
};
