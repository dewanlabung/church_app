import React from 'react';
import VersesManager from './admin/VersesManager';
import BlessingsManager from './admin/BlessingsManager';
import PrayersManager from './admin/PrayersManager';
import EventsManager from './admin/EventsManager';
import PostsManager from './admin/PostsManager';
import SermonsManager from './admin/SermonsManager';
import BooksManager from './admin/BooksManager';
import BibleStudiesManager from './admin/BibleStudiesManager';
import ReviewsManager from './admin/ReviewsManager';
import GalleriesManager from './admin/GalleriesManager';
import MinistriesManager from './admin/MinistriesManager';
import ContactsManager from './admin/ContactsManager';
import NewsletterManager from './admin/NewsletterManager';
import DonationsManager from './admin/DonationsManager';
import UsersManager from './admin/UsersManager';
import SettingsManager from './admin/SettingsManager';

const components = {
    'verses': VersesManager,
    'blessings': BlessingsManager,
    'prayers': PrayersManager,
    'events': EventsManager,
    'posts': PostsManager,
    'sermons': SermonsManager,
    'books': BooksManager,
    'bible-studies': BibleStudiesManager,
    'reviews': ReviewsManager,
    'galleries': GalleriesManager,
    'ministries': MinistriesManager,
    'contacts': ContactsManager,
    'newsletter': NewsletterManager,
    'donations': DonationsManager,
    'users': UsersManager,
    'settings': SettingsManager,
};

export default function AdminApp({ section }) {
    const Component = components[section];

    if (!Component) {
        return <div className="p-8 text-center text-gray-500">Section not found: {section}</div>;
    }

    return <Component />;
}
