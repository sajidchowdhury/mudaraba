import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import { DirectorForm } from "./DirectorForm";
import { UserPlus } from "lucide-react";

export default function DirectorCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        mobile: "",
        address: "",
        is_my: false,
    });

    return (
        <AuthenticatedLayout title="New Director">
            <Head title="New Director" />
            <DirectorForm
                mode="create"
                data={data}
                setData={setData}
                submit={(url) => post(url, { preserveScroll: true })}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}
