import { NavLink, Outlet} from "react-router-dom";

export default function AppLayout() {
    return (
        <div className="app-shell">
            <header className="site-header">
                <NavLink className="brand" to="/">EventManager</NavLink>

                <nav className="main-nav" aria-label="Główna nawigacja">
                    <NavLink to="/events">Wydarzenia</NavLink>
                    <NavLink to="/my-applications">Moje zgłoszenia</NavLink>
                </nav>

                <div className="auth-nav">
                    <NavLink to="/login">Zaloguj się</NavLink>
                    <NavLink className="button button-primary" to="/register">Zarejestruj się</NavLink>
                </div>
            </header>

            <main className="page-content">
                <Outlet />
            </main>
        </div>
    )
}