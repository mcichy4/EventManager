import { NavLink, Outlet, useNavigate } from "react-router-dom";
import { useAuth } from "../../auth/useAuth";

export default function AppLayout() {
  const { isAuthenticated, isLoading, logout, user } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate("/");
  };

  return (
    <div className="app-shell">
      <header className="site-header">
        <NavLink className="brand" to="/">
          EventManager
        </NavLink>

        <nav className="main-nav" aria-label="Główna nawigacja">
          <NavLink to="/events">Wydarzenia</NavLink>
          {isAuthenticated && (
            <>
              <NavLink to="/my-applications">Moje zgłoszenia</NavLink>
              <NavLink to="/my-organizers">Moi organizatorzy</NavLink>
            </>
          )}
        </nav>

        <div className="auth-nav">
          {!isLoading &&
            (isAuthenticated ? (
              <>
                <span className="user-name">{user.name}</span>
                <button
                  className="text-button"
                  type="button"
                  onClick={handleLogout}
                >
                  Wyloguj się
                </button>
              </>
            ) : (
              <>
                <NavLink to="/login">Zaloguj się</NavLink>
                <NavLink className="button button-primary" to="/register">
                  Zarejestruj się
                </NavLink>
              </>
            ))}
        </div>
      </header>

      <main className="page-content">
        <Outlet />
      </main>
    </div>
  );
}
