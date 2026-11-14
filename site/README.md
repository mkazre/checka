# CheckA+ marketing website

This folder holds the static pages designed in Claude Design. The platform serves them on `checkaplus.co.za` and `www.` through `Platform\Controllers\Site`. Clean URLs (`/pricing`) and the original file names (`Pricing.html`) both work.

**Live connections:**
- **`config.js`**: the platform replaces `checkaplus.co.za` with the root domain set in the admin panel. This makes the same files work on a local test domain.
- **Pricing.html** reads `/api/plans`, built live from the admin panel:
  - Packages that are active and public.
  - Settings → Website: add-ons, FAQ and the enterprise box.
- **SignUp.html**:
  - Checks workspace addresses live against `/api/workspace-availability`.
  - Posts to `/signup`.
  - A trial takes the customer to the "setting up" page; pay now takes them to the pay page.
- **Login.html** sends people to `<workspace>.checkaplus.co.za/login`. "Forgot your workspace?" emails their links.
- **Contact.html** posts to `/api/contact`. The enquiry is saved under **Leads** and emailed to you.

**Edit freely:** text, sections and styling. Keep the element IDs the scripts use.
