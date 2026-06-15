# Subscription Core — Full Documentation

[![PHP](https://img.shields.io/badge/PHP-^8.3-777BB4)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-^11.0|^12.0|^13.0-FF2D20)](https://laravel.com)

**A powerful, extensible Laravel subscription management package** — manages plans, features, subscriptions, and usage tracking with **Multi-Tenancy** support. Designed to be flexible, fully customizable, and ready to integrate with any payment gateway.

---

## 🌟 Overview

This package provides a complete subscription management system for Laravel applications. It is built **without payment gateways**, allowing you to integrate with any payment system (Stripe, PayPal, Moyasar, Tabby, etc.).

### Key Features

| Feature                    | Description                                                           |
| -------------------------- | --------------------------------------------------------------------- |
| ✅ **Plan Management**     | Create plans with monthly, yearly, or lifetime billing cycles         |
| ✅ **Features**            | 3 types: Toggle (on/off), Consumable (usage-based), Limit (max count) |
| ✅ **Full Subscriptions**  | Subscribe, cancel, renew, switch plan, pause, resume                  |
| ✅ **Add-ons**             | Attach extra services to primary subscriptions                        |
| ✅ **Usage Tracking**      | Track feature consumption with automatic limit enforcement            |
| ✅ **Feature Aggregation** | Merge features from primary subscription + add-ons automatically      |
| ✅ **Multi-Tenancy**       | Scope per tenant (SaaS)                                               |
| ✅ **Event System**        | Rich events for the full subscription lifecycle                       |
| ✅ **Artisan Commands**    | Expire subscriptions, reset usage, generate recurring invoices        |
| ✅ **Middleware**          | Protect routes based on feature access                                |
| ✅ **Polymorphic**         | Works with any model (User, Team, Organization...)                    |
| ✅ **Extensibility**       | Replace any component via Contracts                                   |
| ✅ **No Invoices**         | Leave billing to your external payment system                         |

---

## 📋 Table of Contents

| #   | Section                      | Link                                   |
| --- | ---------------------------- | -------------------------------------- |
| 1   | Installation & Configuration | [`installation.md`](installation.md)   |
| 2   | Architecture                 | [`architecture.md`](architecture.md)   |
| 3   | Database Schema              | [`database.md`](database.md)           |
| 4   | Components                   | [`components.md`](components.md)       |
| 5   | Usage Guide                  | [`usage.md`](usage.md)                 |
| 6   | Multi-Tenancy                | [`multi-tenancy.md`](multi-tenancy.md) |
| 7   | Customization                | [`customization.md`](customization.md) |
| 8   | Advanced Scenarios           | [`advanced.md`](advanced.md)           |
| 9   | Testing                      | [`testing.md`](testing.md)             |

---
