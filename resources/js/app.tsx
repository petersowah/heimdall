import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { Layout } from '@/components/Layout';
import { getBaseUrl } from '@/lib/utils';
import Dashboard from '@/pages/Dashboard';
import CreateDomain from '@/pages/domains/Create';
import EditDomain from '@/pages/domains/Edit';
import DomainsIndex from '@/pages/domains/Index';
import ShowDomain from '@/pages/domains/Show';
import NotificationSettingsPage from '@/pages/settings/Notifications';
import './app.css';

const el = document.getElementById('heimdall-app');

if (el) {
    const baseUrl = new URL(getBaseUrl()).pathname;

    createRoot(el).render(
        <React.StrictMode>
            <BrowserRouter basename={baseUrl}>
                <Layout>
                    <Routes>
                        <Route path="/" element={<Dashboard />} />
                        <Route path="/domains" element={<DomainsIndex />} />
                        <Route path="/domains/create" element={<CreateDomain />} />
                        <Route path="/domains/:id" element={<ShowDomain />} />
                        <Route path="/domains/:id/edit" element={<EditDomain />} />
                        <Route path="/settings/notifications" element={<NotificationSettingsPage />} />
                    </Routes>
                </Layout>
            </BrowserRouter>
        </React.StrictMode>
    );
}
