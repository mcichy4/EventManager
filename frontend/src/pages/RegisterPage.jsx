import { Navigate, useNavigate } from "react-router-dom";
import { useAuth } from "../auth/useAuth";
import RegisterForm from "../components/RegisterForm";

export default function RegisterPage() {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }
  const handleRegister = () => {
    navigate("/");
  };

  return <RegisterForm onRegister={handleRegister} />;
}
