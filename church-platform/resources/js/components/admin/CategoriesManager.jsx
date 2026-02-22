import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

export default function CategoriesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState({});
    const [modal, setModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ name: '', type: 'post', parent_id: '', description: '', sort_order: 0, is_active: true });
    const [alert, setAlert] = useState(null);
    const [typeFilter, setTypeFilter] = useState('');

    useEffect(() => { fetchItems(); }, [typeFilter]);

    const fetchItems = async (page = 1) => {
        try {
            let url = `/api/categories/admin?page=${page}`;
            if (typeFilter) url += `&type=${typeFilter}`;
            const data = await get(url);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', type: 'post', parent_id: '', description: '', sort_order: 0, is_active: true });
        setModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            name: item.name || '', type: item.type || 'post', parent_id: item.parent_id || '',
            description: item.description || '', sort_order: item.sort_order || 0, is_active: item.is_active ?? true,
        });
        setModal(true);
    };

    const handleSubmit = async (e) => {
        if (e) e.preventDefault();
        try {
            const payload = { ...form };
            if (!payload.parent_id) delete payload.parent_id;
            if (editing) {
                await put(`/api/categories/${editing.id}`, payload);
                setAlert({ type: 'success', message: 'Category updated.' });
            } else {
                await post('/api/categories', payload);
                setAlert({ type: 'success', message: 'Category created.' });
            }
            setModal(false);
            fetchItems(meta.current_page || 1);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm(`Delete category "${item.name}"?`)) return;
        try {
            await del(`/api/categories/${item.id}`);
            setAlert({ type: 'success', message: 'Category deleted.' });
            fetchItems(meta.current_page || 1);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    };

    const typeOptions = [
        { value: 'post', label: 'Post' }, { value: 'page', label: 'Page' },
        { value: 'sermon', label: 'Sermon' }, { value: 'book', label: 'Book' },
        { value: 'bible-study', label: 'Bible Study' },
    ];

    const columns = [
        { key: 'name', label: 'Name' },
        { key: 'slug', label: 'Slug' },
        { key: 'type', label: 'Type', render: (r) => <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{r.type}</span> },
        { key: 'children', label: 'Sub-categories', render: (r) => (r.children || []).length },
        { key: 'sort_order', label: 'Order' },
        { key: 'is_active', label: 'Status', render: (r) => <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${r.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{r.is_active ? 'Active' : 'Inactive'}</span> },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Categories</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Category</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="mb-4 flex gap-2 flex-wrap">
                <button onClick={() => setTypeFilter('')} className={`px-3 py-1.5 rounded-lg text-sm font-medium ${!typeFilter ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}>All</button>
                {typeOptions.map(opt => (
                    <button key={opt.value} onClick={() => setTypeFilter(opt.value)} className={`px-3 py-1.5 rounded-lg text-sm font-medium ${typeFilter === opt.value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}>{opt.label}</button>
                ))}
            </div>
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(r) => (
                    <div className="flex gap-2">
                        <button onClick={() => openEdit(r)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(r)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={modal} onClose={() => setModal(false)} title={editing ? 'Edit Category' : 'New Category'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Name" name="name" value={form.name} onChange={handleChange} required />
                    <FormField label="Type" name="type" type="select" value={form.type} onChange={handleChange} options={typeOptions} />
                    <FormField label="Parent Category" name="parent_id" type="select" value={form.parent_id} onChange={handleChange} options={[{ value: '', label: 'None (Top Level)' }, ...items.filter(i => !editing || i.id !== editing.id).map(i => ({ value: String(i.id), label: i.name }))]} />
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} rows={2} />
                    <FormField label="Sort Order" name="sort_order" type="number" value={form.sort_order} onChange={handleChange} />
                    <FormField label="Active" name="is_active" type="checkbox" value={form.is_active} onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{editing ? 'Update' : 'Create'}</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
