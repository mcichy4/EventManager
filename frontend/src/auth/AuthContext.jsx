import { useEffect, useMemo, useState } from "react";
import {
  getCurrentUser,
  hasToken,
  login as loginRequest,
  logout as logoutRequest,
  register as registerRequest,
} from "../api/auth";
import { AuthContext } from "./context";

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const restoreSession = async () => {
      if (!hasToken()) {
        setIsLoading(false);
        return;
      }

      try {
        setUser(await getCurrentUser());
      } catch {
        await logoutRequest();
      } finally {
        setIsLoading(false);
      }
    };

    restoreSession();
  }, []);

  const value = useMemo(
    () => ({
      user,
      isLoading,
      isAuthenticated: Boolean(user),
      login: async (credentials) => {
        const loggedInUser = await loginRequest(credentials);
        setUser(loggedInUser);
        return loggedInUser;
      },
      register: async (credentials) => {
        const registeredUser = await registerRequest(credentials);
        setUser(registeredUser);
        return registeredUser;
      },
      logout: async () => {
        await logoutRequest();
        setUser(null);
      },
    }),
    [isLoading, user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
