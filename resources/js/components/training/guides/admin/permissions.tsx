import { ListBasics } from '@/components/training/guides/shared/list-basics';
import {
    Callout,
    Field,
    Fields,
    Guide,
    GuideSection,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    Ui,
} from '@/components/training/vocabulary';

/**
 * Admin → Permissions (`admin.permissions.index`) — the read-only catalogue.
 * Source: documentation/admin.md §3.1–§3.2.
 */
export default function PermissionsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Every permission in the application, in plain words: what it allows, where, and which roles hold it. The screen is for reading only. Permissions are part of the application itself; you give them to people through roles."
                    bn="অ্যাপ্লিকেশনের প্রতিটি পারমিশন, সহজ ভাষায়: এটি কী অনুমতি দেয়, কোথায়, এবং কোন কোন রোলে আছে। স্ক্রিনটি শুধু পড়ার জন্য। পারমিশনগুলো অ্যাপ্লিকেশনেরই অংশ; মানুষকে এগুলো দেওয়া হয় রোলের মাধ্যমে।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.permissions.view</Ui>.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.permissions.view</Ui>।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en={
                            <>
                                Open Admin → Permissions. Pick a module in the{' '}
                                <Ui>Module</Ui> filter to see only its
                                permissions, or type part of a name, such as{' '}
                                <Ui>users</Ui>.
                            </>
                        }
                        bn={
                            <>
                                Admin → Permissions খুলুন। শুধু একটি মডিউলের
                                পারমিশন দেখতে <Ui>Module</Ui> ফিল্টারে সেটি বেছে
                                নিন, অথবা নামের অংশ লিখুন, যেমন <Ui>users</Ui>।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="Each permission with its module, what it allows, and the roles that hold it."
                            bn="প্রতিটি পারমিশন — এর মডিউল, এটি কী অনুমতি দেয়, এবং যে রোলগুলোতে এটি আছে।"
                        />
                    </Step>
                    <Step
                        en="To change who holds a permission, edit a role on the Roles screen. Nothing here can be changed."
                        bn="কোন পারমিশন কার কাছে থাকবে তা বদলাতে Roles স্ক্রিনে একটি রোল সম্পাদনা করুন। এখানে কিছুই বদলানো যায় না।"
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Permission"
                        en="Its name: module, then what it covers, then the action — admin.users.reset-password is Admin, users, setting a password."
                        bn="এর নাম: প্রথমে মডিউল, তারপর কী নিয়ে, তারপর কাজটি — admin.users.reset-password মানে Admin, ব্যবহারকারী, পাসওয়ার্ড দেওয়া।"
                    />
                    <Field
                        label="Allows"
                        en="What holding it lets someone do, and where on screen. Some carry a note — for example that only a super admin has it until someone grants it by hand."
                        bn="এটি থাকলে কেউ কী করতে পারেন, এবং স্ক্রিনের কোথায়। কোনো কোনোটির সাথে একটি নোট থাকে — যেমন, কেউ নিজে হাতে না দেওয়া পর্যন্ত এটি কেবল সুপার অ্যাডমিনের।"
                    />
                    <Field
                        label="Roles"
                        en="The roles that hold it. super-admin is on every one: it holds them all, and passes every check besides."
                        bn="যে রোলগুলোতে এটি আছে। super-admin প্রতিটিতেই থাকে: এর সব পারমিশন আছে, তার ওপর এটি সব যাচাই এমনিতেই পার হয়ে যায়।"
                    />
                </Fields>
                <ListBasics />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="info"
                    en="A screen you open but whose buttons are missing usually means a permission is missing: each action has its own. Find the action here, then check which roles hold it."
                    bn="কোনো স্ক্রিন খুলতে পারছেন কিন্তু এর বোতামগুলো নেই — এর মানে সাধারণত একটি পারমিশন নেই: প্রতিটি কাজের আলাদা পারমিশন। কাজটি এখানে খুঁজে বের করুন, তারপর দেখুন কোন রোলগুলোতে এটি আছে।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.roles"
                    en="Giving a permission to a role."
                    bn="কোনো রোলে পারমিশন যোগ করা।"
                />
            </GuideSection>
        </Guide>
    );
}
