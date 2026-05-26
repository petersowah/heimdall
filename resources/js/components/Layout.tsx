import { AlertCircle, Bell, Globe, LayoutDashboard, Settings } from 'lucide-react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { getBaseUrl } from '@/lib/utils';

function SidebarLink({ to, icon: Icon, label }: { to: string; icon: React.ElementType; label: string }) {
    return (
        <NavLink
            to={to}
            className={({ isActive }) =>
                `flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors ${
                    isActive
                        ? 'bg-accent text-accent-foreground font-medium'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                }`
            }
        >
            <Icon className="size-4" />
            {label}
        </NavLink>
    );
}

export function Layout({ children }: { children: React.ReactNode }) {
    const baseUrl = getBaseUrl();

    return (
        <div className="flex min-h-screen bg-background">
            <aside className="hidden w-56 shrink-0 border-r bg-sidebar lg:flex flex-col">
                <div className="flex h-14 items-center gap-2 border-b px-4">
                    <AlertCircle className="size-5 text-primary" />
                    <Link to="/" className="font-semibold text-sm">Heimdall</Link>
                </div>
                <nav className="flex-1 space-y-1 p-3">
                    <SidebarLink to="/" icon={LayoutDashboard} label="Dashboard" />
                    <SidebarLink to="/domains" icon={Globe} label="Domains" />
                    <SidebarLink to="/settings/notifications" icon={Bell} label="Notifications" />
                </nav>
            </aside>

            <div className="flex flex-1 flex-col min-w-0">
                <header className="flex h-14 items-center gap-4 border-b bg-background px-4 lg:px-6">
                    <div className="flex items-center gap-2 lg:hidden">
                        <AlertCircle className="size-5 text-primary" />
                        <span className="font-semibold text-sm">Heimdall</span>
                    </div>
                    <nav className="flex items-center gap-1 lg:hidden">
                        <NavLink
                            to="/"
                            className={({ isActive }) =>
                                `px-3 py-1.5 rounded text-sm ${isActive ? 'bg-accent font-medium' : 'text-muted-foreground hover:bg-accent'}`
                            }
                        >
                            Dashboard
                        </NavLink>
                        <NavLink
                            to="/domains"
                            className={({ isActive }) =>
                                `px-3 py-1.5 rounded text-sm ${isActive ? 'bg-accent font-medium' : 'text-muted-foreground hover:bg-accent'}`
                            }
                        >
                            Domains
                        </NavLink>
                        <NavLink
                            to="/settings/notifications"
                            className={({ isActive }) =>
                                `px-3 py-1.5 rounded text-sm ${isActive ? 'bg-accent font-medium' : 'text-muted-foreground hover:bg-accent'}`
                            }
                        >
                            Notifications
                        </NavLink>
                    </nav>
                </header>

                <main className="flex-1 overflow-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}
