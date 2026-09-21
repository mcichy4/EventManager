import { useNavigate } from "react-router-dom";
import RegisterForm from "../components/RegisterForm";

export default function RegisterPage() {
  const navigate = useNavigate();
  const handleRegister = () => {
    navigate("/");
  };

  return <RegisterForm onRegister={handleRegister} />;
}
