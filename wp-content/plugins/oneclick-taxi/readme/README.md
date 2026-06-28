# OneClick Taxi Ordering App
### "Install it, follow the steps, and in 20 minutes you'll have your own taxi ordering app."

---

## What You Get

A complete, professional taxi booking platform for your WordPress site:

- 📍 **Live Google Maps routing** — customers see real-time routes and fare estimates
- 📋 **Online booking form** — 3-step booking flow (Ride Details → Contact → Done)
- 💳 **Square card payments** — accept credit/debit cards at booking
- 📅 **Google Calendar auto-scheduling** — every booking becomes a calendar event with drive-time duration
- 📲 **Push notifications** — instant alerts to your phone via Pushover (free app)
- 🗺️ **Flat rate destinations** — fixed pricing for popular routes grouped by direction
- 🎨 **4-color brand theming** — match your company colors in minutes
- 🎟️ **Coupon system** — create discount codes for repeat customers
- 👨‍💼 **Driver portal** — separate login for viewing upcoming bookings

---

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- SSL certificate (HTTPS) — required for Square payments
- A Google account (for Maps + Calendar)
- A Square account (free at squareup.com)
- Pushover account ($5 one-time purchase at pushover.net)

---

## Installation

### Step 1: Install the Plugin

1. Log into your WordPress admin panel
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload `OneClick_TaxiOrderingApp.zip`
4. Click **Install Now** then **Activate**

### Step 2: Follow the Setup Wizard

After activation, you'll see **OneClick Taxi** in your admin sidebar with a **🚀 Setup Wizard**. Click it and follow the 10 steps:

| Step | What You'll Do | Time |
|------|---------------|------|
| 1 | Welcome | 1 min |
| 2 | Enter business name, phone, hours, pricing | 3 min |
| 3 | Set up Google Maps API key | 5 min |
| 4 | Connect Google Calendar | 5 min |
| 5 | Connect Square payments | 3 min |
| 6 | Set up Pushover notifications | 2 min |
| 7 | Choose your brand colors | 1 min |
| 8 | Upload your logo | 1 min |
| 9 | Add flat rate destinations | optional |
| 10 | Launch! | done |

---

## Getting Your API Keys

### Google Maps API Key (Step 3)

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create a new project (name it anything)
3. Go to **APIs & Services → Library**
4. Search and enable ALL of these:
   - **Maps JavaScript API**
   - **Places API**
   - **Geocoding API**
   - **Directions API**
5. Go to **APIs & Services → Credentials**
6. Click **+ Create Credentials → API Key**
7. Copy the key — paste it in Step 3 of the wizard

**Recommended:** Restrict the key to your domain under **Application restrictions → HTTP referrers**

---

### Google Calendar (Step 4)

1. In the same Google Cloud project, go to **APIs & Services → Library**
2. Enable **Google Calendar API**
3. Go to **APIs & Services → OAuth consent screen**
   - User type: External
   - Fill in your app name and email
4. Go to **Credentials → + Create Credentials → OAuth 2.0 Client ID**
   - Application type: **Web application**
   - Add to Authorized redirect URIs: `https://YOURSITE.com/wp-admin/admin.php?page=oct-calendar`
5. Copy the **Client ID** and **Client Secret**
6. Paste them in Step 4 of the wizard and click **Save & Authorize**
7. Sign in with the Google account whose calendar you want to use
8. Click **Allow**

---

### Square Payments (Step 5)

1. Go to [developer.squareup.com](https://developer.squareup.com)
2. Sign in or create a free Square account
3. Click **+ Create Application** — name it your business name
4. Go to the **Credentials** tab:
   - Copy **Application ID** → paste as "Square Application ID"
   - Copy **Access Token** → paste as "Square Access Token"
5. Go to [squareup.com/dashboard/locations](https://squareup.com/dashboard/locations)
   - Copy your **Location ID** → paste as "Square Location ID"
6. Click **Verify & Continue** — the wizard will test the connection

---

### Pushover Push Notifications (Step 6)

1. Go to [pushover.net](https://pushover.net) and create a free account
2. Download the **Pushover app** on your iPhone or Android ($5 one-time purchase)
3. Log into pushover.net — find your **Email Alias** on the dashboard
   - It looks like: `abc123xyz@pomail.net`
4. Paste it in Step 6 and click **Send Test**
5. Check your phone — a test notification should arrive within seconds

---

## Customizing Colors

Go to **OneClick Taxi → 🎨 Brand & Colors** to set 4 colors:

| Color | Used For |
|-------|---------|
| **Primary** | Buttons, links, highlights |
| **Secondary** | Header, navigation background |
| **Accent** | Prices, totals, call-to-action |
| **Background** | Page background color |

Changes apply instantly. Use your taxi company's brand colors for a professional look.

---

## Adding Flat Rate Destinations

Go to **OneClick Taxi → 🗺️ Flat Rates** to add fixed-price routes.

- Group destinations by zone (e.g. `north_bound`, `west_bound`, `airport`)
- Set a base rate for 1-2 passengers
- The system automatically adds 40% for 3+ passengers
- When a customer selects a flat rate destination, the fare auto-applies

---

## Booking Form

A **Book a Ride** page is automatically created when you activate the plugin. You can also add the booking form to any page using the shortcode:

```
[oct_booking_form]
```

---

## Email & SMS Notifications

Every booking sends:
1. A detailed confirmation email to your business email
2. A push notification to your phone via Pushover

To configure:
- Business email: **OneClick Taxi → General Settings → Email**
- Pushover: **OneClick Taxi → General Settings → Pushover Email**

---

## Troubleshooting

**Maps not loading?**
- Check your Google Maps API key is correct
- Make sure all 4 APIs are enabled (Maps JavaScript, Places, Geocoding, Directions)
- Check your key restrictions include your domain

**Calendar not posting?**
- Go to **OneClick Taxi → 📅 Calendar Auth** and click Re-authorize
- Make sure Google Calendar API is enabled in your Cloud project

**Square payment failing?**
- Verify your site has SSL (HTTPS)
- Double-check your Location ID — it's different from your Account ID

**Push notifications not arriving?**
- Make sure your Pushover account email is verified
- Check that notifications are allowed for the Pushover app on your phone
- Try sending a test from **OneClick Taxi → General Settings**

---

## Support

Need help with setup? Contact the developer:
- **Website:** [asuperiortransportation.com](https://asuperiortransportation.com)
- **Email:** stalcollc@gmail.com

We offer paid setup assistance to get your app running in minutes.

---

## License

GPL v2 or later. You may use this plugin on one site per purchase. Redistribution requires written permission.

---

*OneClick Taxi Ordering App v1.0.0*
*Built by A Superior Transportation & Logistics*
