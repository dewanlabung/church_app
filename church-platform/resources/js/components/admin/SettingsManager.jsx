import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

function SectionHeader({ icon, title, description }) {
    return (
        <div className="mb-6 pb-3 border-b border-gray-200">
            <div className="flex items-center gap-3">
                <div className="flex-shrink-0 w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                    {icon}
                </div>
                <div>
                    <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                    {description && <p className="text-sm text-gray-500">{description}</p>}
                </div>
            </div>
        </div>
    );
}

export default function SettingsManager() {
    const [form, setForm] = useState({
        church_name: '',
        tagline: '',
        description: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip_code: '',
        country: '',
        pastor_name: '',
        pastor_title: '',
        mission_statement: '',
        vision_statement: '',
        about_text: '',
        facebook_url: '',
        twitter_url: '',
        instagram_url: '',
        youtube_url: '',
        tiktok_url: '',
        primary_color: '#4f46e5',
        secondary_color: '#7c3aed',
        footer_text: '',
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
        service_times: '',
    });
    const [files, setFiles] = useState({ logo: null, banner: null, favicon: null });
    const [previews, setPreviews] = useState({ logo: null, banner: null, favicon: null });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        setLoading(true);
        try {
            const data = await get('/api/settings');
            const settings = data.data || data;
            const updated = { ...form };
            Object.keys(updated).forEach((key) => {
                if (settings[key] !== undefined && settings[key] !== null) {
                    updated[key] = settings[key];
                }
            });
            setForm(updated);

            // Set existing file previews from the server
            const newPreviews = { logo: null, banner: null, favicon: null };
            if (settings.logo_url) newPreviews.logo = settings.logo_url;
            if (settings.banner_url) newPreviews.banner = settings.banner_url;
            if (settings.favicon_url) newPreviews.favicon = settings.favicon_url;
            setPreviews(newPreviews);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load settings: ' + e.message });
        }
        setLoading(false);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm({ ...form, [name]: value });
    };

    const handleFileChange = (e) => {
        const { name, files: fileList } = e.target;
        if (fileList && fileList[0]) {
            setFiles((prev) => ({ ...prev, [name]: fileList[0] }));
            // Create local preview
            const reader = new FileReader();
            reader.onload = (ev) => {
                setPreviews((prev) => ({ ...prev, [name]: ev.target.result }));
            };
            reader.readAsDataURL(fileList[0]);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setAlert(null);
        try {
            const formData = new FormData();

            // Append all text fields
            Object.keys(form).forEach((key) => {
                if (form[key] !== null && form[key] !== undefined) {
                    formData.append(key, form[key]);
                }
            });

            // Append files
            if (files.logo) formData.append('logo', files.logo);
            if (files.banner) formData.append('banner', files.banner);
            if (files.favicon) formData.append('favicon', files.favicon);

            // Laravel PUT via POST with _method override for FormData
            formData.append('_method', 'PUT');
            await upload('/api/settings', formData, 'POST');

            setAlert({ type: 'success', message: 'Settings saved successfully.' });
            // Clear file inputs after successful save
            setFiles({ logo: null, banner: null, favicon: null });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to save settings: ' + e.message });
        }
        setSaving(false);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <svg className="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span className="ml-3 text-gray-500 text-sm">Loading settings...</span>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Site Settings</h2>
                <button
                    onClick={handleSubmit}
                    disabled={saving}
                    className="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    {saving && (
                        <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    )}
                    {saving ? 'Saving...' : 'Save Settings'}
                </button>
            </div>

            <Alert {...alert} onClose={() => setAlert(null)} />

            <form onSubmit={handleSubmit} className="space-y-8">

                {/* Church Info Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        }
                        title="Church Information"
                        description="Basic details about your church"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Church Name" name="church_name" value={form.church_name} onChange={handleChange} required placeholder="Enter church name" />
                        <FormField label="Tagline" name="tagline" value={form.tagline} onChange={handleChange} placeholder="A short tagline" />
                    </div>
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} placeholder="Brief description of your church" rows={3} />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} placeholder="contact@church.com" />
                        <FormField label="Phone" name="phone" value={form.phone} onChange={handleChange} placeholder="(555) 123-4567" />
                    </div>
                    <FormField label="Address" name="address" value={form.address} onChange={handleChange} placeholder="Street address" />
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-x-6">
                        <FormField label="City" name="city" value={form.city} onChange={handleChange} placeholder="City" />
                        <FormField label="State" name="state" value={form.state} onChange={handleChange} placeholder="State" />
                        <FormField label="Zip Code" name="zip_code" value={form.zip_code} onChange={handleChange} placeholder="Zip" />
                        <FormField label="Country" name="country" value={form.country} onChange={handleChange} placeholder="Country" />
                    </div>
                </div>

                {/* Leadership Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        }
                        title="Leadership"
                        description="Pastor and leadership information"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Pastor Name" name="pastor_name" value={form.pastor_name} onChange={handleChange} placeholder="Pastor's full name" />
                        <FormField label="Pastor Title" name="pastor_title" value={form.pastor_title} onChange={handleChange} placeholder="e.g. Senior Pastor" />
                    </div>
                </div>

                {/* Mission Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        }
                        title="Mission & Vision"
                        description="Your church's mission, vision, and about information"
                    />
                    <FormField label="Mission Statement" name="mission_statement" type="textarea" value={form.mission_statement} onChange={handleChange} placeholder="What is your church's mission?" rows={3} />
                    <FormField label="Vision Statement" name="vision_statement" type="textarea" value={form.vision_statement} onChange={handleChange} placeholder="What is your church's vision?" rows={3} />
                    <FormField label="About Text" name="about_text" type="textarea" value={form.about_text} onChange={handleChange} placeholder="Tell visitors about your church..." rows={4} />
                </div>

                {/* Social Media Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        }
                        title="Social Media"
                        description="Links to your social media profiles"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Facebook URL" name="facebook_url" type="url" value={form.facebook_url} onChange={handleChange} placeholder="https://facebook.com/yourchurch" />
                        <FormField label="Twitter / X URL" name="twitter_url" type="url" value={form.twitter_url} onChange={handleChange} placeholder="https://twitter.com/yourchurch" />
                        <FormField label="Instagram URL" name="instagram_url" type="url" value={form.instagram_url} onChange={handleChange} placeholder="https://instagram.com/yourchurch" />
                        <FormField label="YouTube URL" name="youtube_url" type="url" value={form.youtube_url} onChange={handleChange} placeholder="https://youtube.com/@yourchurch" />
                        <FormField label="TikTok URL" name="tiktok_url" type="url" value={form.tiktok_url} onChange={handleChange} placeholder="https://tiktok.com/@yourchurch" />
                    </div>
                </div>

                {/* Appearance Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        }
                        title="Appearance"
                        description="Customize colors and branding"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <div className="mb-4">
                            <label htmlFor="field-primary_color" className="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                            <div className="flex items-center gap-3">
                                <input
                                    id="field-primary_color"
                                    type="color"
                                    name="primary_color"
                                    value={form.primary_color || '#4f46e5'}
                                    onChange={handleChange}
                                    className="h-10 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                />
                                <input
                                    type="text"
                                    value={form.primary_color || '#4f46e5'}
                                    onChange={(e) => setForm({ ...form, primary_color: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                    placeholder="#4f46e5"
                                />
                            </div>
                        </div>
                        <div className="mb-4">
                            <label htmlFor="field-secondary_color" className="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
                            <div className="flex items-center gap-3">
                                <input
                                    id="field-secondary_color"
                                    type="color"
                                    name="secondary_color"
                                    value={form.secondary_color || '#7c3aed'}
                                    onChange={handleChange}
                                    className="h-10 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                />
                                <input
                                    type="text"
                                    value={form.secondary_color || '#7c3aed'}
                                    onChange={(e) => setForm({ ...form, secondary_color: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                    placeholder="#7c3aed"
                                />
                            </div>
                        </div>
                    </div>
                    <FormField label="Footer Text" name="footer_text" value={form.footer_text} onChange={handleChange} placeholder="e.g. &copy; 2026 Your Church. All rights reserved." />
                </div>

                {/* SEO Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        }
                        title="SEO"
                        description="Search engine optimization settings"
                    />
                    <FormField label="Meta Title" name="meta_title" value={form.meta_title} onChange={handleChange} placeholder="Page title for search engines" />
                    <FormField label="Meta Description" name="meta_description" type="textarea" value={form.meta_description} onChange={handleChange} placeholder="Brief description for search results" rows={2} />
                    <FormField label="Meta Keywords" name="meta_keywords" value={form.meta_keywords} onChange={handleChange} placeholder="keyword1, keyword2, keyword3" />
                </div>

                {/* Service Times Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        }
                        title="Service Times"
                        description="When your church holds services"
                    />
                    <FormField label="Service Times" name="service_times" type="textarea" value={form.service_times} onChange={handleChange} placeholder="Sunday: 9:00 AM, 11:00 AM&#10;Wednesday: 7:00 PM" rows={4} />
                </div>

                {/* File Uploads Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        }
                        title="Files & Media"
                        description="Upload your logo, banner, and favicon"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <FormField label="Logo" name="logo" type="file" onChange={handleFileChange} />
                            {previews.logo && (
                                <div className="mt-2 p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <img src={previews.logo} alt="Logo preview" className="h-16 object-contain mx-auto" />
                                </div>
                            )}
                        </div>
                        <div>
                            <FormField label="Banner" name="banner" type="file" onChange={handleFileChange} />
                            {previews.banner && (
                                <div className="mt-2 p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <img src={previews.banner} alt="Banner preview" className="h-16 object-contain mx-auto" />
                                </div>
                            )}
                        </div>
                        <div>
                            <FormField label="Favicon" name="favicon" type="file" onChange={handleFileChange} />
                            {previews.favicon && (
                                <div className="mt-2 p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <img src={previews.favicon} alt="Favicon preview" className="h-16 object-contain mx-auto" />
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Submit */}
                <div className="flex justify-end pb-4">
                    <button
                        type="submit"
                        disabled={saving}
                        className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        {saving && (
                            <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        )}
                        {saving ? 'Saving...' : 'Save Settings'}
                    </button>
                </div>
            </form>
        </div>
    );
}
